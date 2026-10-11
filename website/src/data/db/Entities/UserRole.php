<?php

namespace database;

enum UserRole: string
{
  case OWNER = 'owner';
  case ADMIN = 'admin';
  case EDITOR = 'editor';
}
