#!/bin/bash

PORT=35729
PID=$(lsof -t -i:$PORT -s TCP:LISTEN)

if [ -n "$PID" ]; then
    # Find the working directory of the process using the port
    PROC_CWD=$(readlink -f /proc/$PID/cwd 2>/dev/null)
    CURRENT_DIR=$(pwd)
    
    # Get absolute path of this project's watcher file
    MY_WATCHER_CJS=$(readlink -f "$(dirname "$0")/watcher.cjs")
    
    # Get the command line arguments of the process
    CMDLINE=$(cat /proc/$PID/cmdline 2>/dev/null | tr '\0' ' ')
    
    # Check if the process is the watcher of the current project
    IS_CURRENT_PROJECT_WATCHER=false
    if [[ "$CMDLINE" == *"watcher.cjs"* ]]; then
        # Resolve the relative or absolute argument path against the process's working directory
        for arg in $(cat /proc/$PID/cmdline 2>/dev/null | tr '\0' '\n'); do
            if [[ "$arg" == *"watcher.cjs"* ]]; then
                ABS_ARG_PATH=$(readlink -f "$PROC_CWD/$arg" 2>/dev/null)
                if [ "$ABS_ARG_PATH" = "$MY_WATCHER_CJS" ]; then
                    IS_CURRENT_PROJECT_WATCHER=true
                    break
                fi
            fi
        done
    fi

    if [ "$IS_CURRENT_PROJECT_WATCHER" = true ]; then
        echo -e "\x1b[32m[Watcher Script] Killing leftover watcher process ($PID) from this project...\x1b[0m"
        kill "$PID"
        sleep 1
    else
        echo -e "\x1b[33m[Watcher Script] Port $PORT is being used by another project/process: $PROC_CWD ($CMDLINE)\x1b[0m"
        
        # Check if the session is interactive to ask the user
        if [ -t 0 ]; then
            read -p "[Watcher Script] Do you want to kill that process to start this watcher? [y/N] " yn
            case $yn in
                [Yy]* ) 
                    echo -e "\x1b[31m[Watcher Script] Killing process $PID...\x1b[0m"
                    kill "$PID"
                    sleep 1
                    ;;
                * ) 
                    echo "[Watcher Script] Proceeding without starting the watcher..."
                    exit 0
                    ;;
            esac
        else
            echo "[Watcher Script] Non-interactive environment. Proceeding without starting the watcher..."
            exit 0
        fi
    fi
fi

# Run the watcher
node watcher.cjs
