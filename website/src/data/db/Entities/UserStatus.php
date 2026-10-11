<?php

namespace database;

enum UserStatus: string
{
  case CONFIRMED = 'confirmed';
  case UNCONFIRMED = 'unconfirmed';
}
