<?php
namespace database;
use mysqli;
use mysqli_driver;

class Database{
	public ?mysqli $connection;
	private static ?Database $_instance = null;
	
	private $_isInTransaction = false;
	
	function isInTransaction() : bool {
		return $this->_isInTransaction;
	}

	private static bool $_logTransactions = false;
	public static function setLogTransactions( bool $log = true ){
		self::$_logTransactions = $log;
	}

	private static bool $_logTransactionsWithTrace = false;
	public static function setLogTransactionsWithTrace( bool $logWithTrace = true ){
		self::$_logTransactionsWithTrace = $logWithTrace;
	}

	public static function instance() : Database {
		if ( self::$_instance === null ){
			self::$_instance = new Database();
		}
		return self::$_instance;
	}

	private function __construct(){
		$driver = new mysqli_driver();
		$driver->report_mode = MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_INDEX;

		$this->connection = null;
		$this->connect();
	}

	function __destruct(){
		$this->connection->close();
	}

	function connect() : bool {
		$db_host = getenv( 'DB_HOST' ) ?: '_hostname_unset';
		$db_user = getenv( 'DB_USER' ) ?: '_db_user_unset';
		$db_password = getenv( 'DB_PASSWORD' ) ?: '_db_password_unset';
		$db_name = getenv( 'DB_NAME' ) ?: '_db_name_unset';
		$db_port = getenv( 'DB_PORT' ) ? (int)getenv( 'DB_PORT' ) : null;
		
		$this->connection = new mysqli(
			$db_host,
			$db_user,
			$db_password,
			$db_name,
			$db_port
		);

		if ( $this->connection->connect_error ){
			trigger_error(
				"DB Connection failed: " .
					$this->connection->connect_error
			);
			return false;
		} else {
			return true;
		}
	}

	public function query(
		string $statement,
		string $types = '',
		array $params = [],
		bool $associated = true,
	){
		$statement = trim( $statement );
		if ( $this->connection->connect_errno && !$this->connect() ){
			trigger_error(
				"DB Connection failed: " .
					$this->connection->connect_error
			);
			return [];
		}

		$query = $this->connection->prepare(
			$statement
		);
		if ( $types != '' ){
			$query->bind_param( $types, ...$params );
		}

		$result = $query->execute();
	
		if (
			preg_match(
				'/^(INSERT INTO|INSERT IGNORE INTO|UPDATE |DELETE )/i',
				$statement
			)
		){
			return $result;
		}
	
		if ( $result ){
			if ( $associated ){
				$r = $query->get_result()->fetch_all( MYSQLI_ASSOC );
				$query->close();
				return $r;
			} else {
				$r = $query->get_result();
				$query->close();
				return $r;
			}
		} else {
			return [];
		}
	}

	public function rawQuery( string $query ) {
		$r = $this->connection->query($query);
		if ( !$r ) {
			error_log(
				"Error in raw query: " .
					$this->connection->error
			);
			return false;
		}
	
		if (
			preg_match(
				'/^(INSERT INTO|UPDATE |DELETE )/i',
				trim($query)
			)
		){
			return $this->connection->affected_rows;
		}

		$data = $r->fetch_all( MYSQLI_ASSOC );
		return $data;
	}

	public function nowFormat() : string {
		return "%Y-%m-%d %H:%i:%s";
	}

	public function now( $timezone = '' ) : string {
		$format = $this->nowFormat();
		if ( $timezone === '' ){
			return $this->rawQuery( "SELECT DATE_FORMAT( NOW(), '$format' ) as 'time'" )[0][ 'time' ];
		} else {
			return $this->rawQuery( "SELECT DATE_FORMAT( CONVERT_TZ( NOW(), @@session.time_zone, '$timezone' ), '$format' ) as 'time'" )[0][ 'time' ];
		}
	}

	public function error() : string {
		return $this->connection->error;
	}

	public function getSQLVersion(){
		$res = $this->connection->query( "SELECT VERSION()" );
		$version = $res->fetch_row()[ 0 ];
		$res->close();
		return $version;
	}

	public function lockRead( string $table ) : bool {
		$queryWorked = $this->connection->query(
			"LOCK TABLES $table READ"
		);
		return $queryWorked;
	}

	public function lockWrite( string $table ) : bool {
		$queryWorked = $this->connection->query(
			"LOCK TABLES $table WRITE"
		);
		return $queryWorked;
	}

	public function lockTablesRead( array $tables ) : bool {
		$queryWorked = $this->connection->query(
			"LOCK TABLES " . implode( ' READ, ', $tables ) . ' READ'
		);
		return $queryWorked;
	}

	public function lockTablesWrite( array $tables ) : bool {
		$queryWorked = $this->connection->query(
			"LOCK TABLES " . implode( ' WRITE, ', $tables ) . ' WRITE'
		);
		return $queryWorked;
	}

	public function unlock() : bool {
		$queryWorked = $this->connection->query(
			"UNLOCK TABLES"
		);
		return $queryWorked;
	}

	public function getLock( string $lockName, int $timeout = 15 ) : bool {
		$r = $this->connection->query(
			"SELECT GET_LOCK( '$lockName', $timeout )"
		);
		return $r->fetch_row()[ 0 ] == 1;
	}

	public function releaseLock( string $lockName ) : bool {
		$r = $this->connection->query(
			"SELECT RELEASE_LOCK( '$lockName' )"
		);
		return $r->fetch_row()[ 0 ] == 1;
	}

	public function getLastInsertId(){
		return $this->connection->insert_id;
	}

	/**
	 * Retrieve normal auto-increment IDs for a list of UUIDs using a raw query.
	 *
	 * @param string $table The table name
	 * @param array $uuids Array of UUID strings
	 * @return array Associative array of [uuid => id]
	 */
	public function getIdsByUUIDs( string $table, array $uuids ) : array {
		if ( empty( $uuids ) ) {
			return [];
		}

		$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

		$escapedUuids = array_map( function( $uuid ) {
			return "'" . $this->connection->real_escape_string( $uuid ) . "'";
		}, $uuids );

		$inClause = implode( ',', $escapedUuids );
		$query = "SELECT uuid, id FROM $table WHERE uuid IN ($inClause)";

		$rows = $this->rawQuery( $query );
		if ( !is_array( $rows ) ) {
			return [];
		}

		$map = [];
		foreach ( $rows as $row ) {
			$map[ $row['uuid'] ] = (int)$row['id'];
		}

		return $map;
	}

	public function enableAutoCommit(){
		$this->connection->autocommit( true );
	}

	public function disableAutoCommit(){
		$this->connection->autocommit( false );
	}

	public function beginTransaction(
		int $flags = 0,
		?string $name = NULL,
		bool $skipIfInTransaction = false,
		bool $log = false,
		bool $logWithTrace = false,
	){
		if ( $this->_isInTransaction && $skipIfInTransaction ){
			return;
		}

		$this->_isInTransaction = true;
		$this->connection->begin_transaction(
			$flags,
			$name
		);
		$this->disableAutoCommit();

		if ( $log || self::$_logTransactions ){
			$logMessage = "###Transaction started###";
			if ( $logWithTrace || self::$_logTransactionsWithTrace ){
				$trace = json_encode( debug_backtrace(), JSON_PRETTY_PRINT );
				$logMessage .= ( "\n$trace\n" );
			}
			error_log( $logMessage );
		}
	}

	public function commit(
		bool $skipIfInTransaction = false,
		bool $log = false,
		bool $logWithTrace = false,
	){
		if ( !$this->_isInTransaction && $skipIfInTransaction ){
			return;
		}
		$this->_isInTransaction = false;
		$this->connection->commit();

		if ( $log || self::$_logTransactions ){
			$logMessage = "###Transaction committed###";
			if ( $logWithTrace || self::$_logTransactionsWithTrace ){
				$trace = json_encode( debug_backtrace(), JSON_PRETTY_PRINT );
				$logMessage .= ( "\n$trace\n" );
			}
			error_log( $logMessage );
		}
	}

	public function rollback( bool $skipIfInTransaction = false ){
		if ( !$this->_isInTransaction && $skipIfInTransaction ){
			return;
		}
		$this->_isInTransaction = false;
		$this->connection->rollback();
	}

	public function explodeFields(
		array $fields,
		?string $entityName = null
	) : string {
		if ( $entityName !== null ){
			$entityName = trim( $entityName ) . ".";
		} else {
			$entityName = '';
		}
		$strFields = implode(
			', ',
			array_map(
				function( $field ) use ( $entityName ){
					return "$entityName$field";
				},
				$fields
			)
		);
		return $strFields;
	}

	public function getBy(
		string $table,
		string $selectorValue,
		string $selector = 'id',
		string $selectorType = 'i',
		array $returnedFields = [ '*' ],
		string $where = ''
	){
		$strFields = $this->explodeFields( $returnedFields, $table );
		$r = Database::instance()->query(
			"SELECT $strFields FROM $table WHERE $selector = ? $where",
			$selectorType,
			[ $selectorValue ]
		);

		return $r;
	}

	public function buildIntervalCondition(
		string $field = 'date_of_creation',
		?string $start = null,
		?string $end = null,
		?string $timezone = null
	){
		$date = $field;
		if ( $timezone !== null && $timezone !== "@@session.time_zone" ){
			$date = "CONVERT_TZ( $field, @@session.time_zone, '$timezone')";
		}

		if ( $start === null ){
			$start = "NOW() - INTERVAL 54 YEAR";
		}
		if ( $end === null ){
			$end = "NOW()";
		}

		
		$condition = "$date BETWEEN $start AND $end";
		return $condition;
	}
}