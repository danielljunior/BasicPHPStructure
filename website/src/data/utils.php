<?php

namespace utils;

use InvalidArgumentException;

/**
 * Validates the given data against the specified fields.
 *
 * @param array $data The data to be validated.
 * @param array $expectedFields The fields to be checked in the data.
 *
 * @return bool Returns true if all fields are present and not empty, false otherwise.
 */
function validateData(
  array $data,
  array $expectedFields,
  array $nullables = [],
  array $emptyables = [],
  &$missingFields = null
) {
  $isValid = true;
  foreach ($expectedFields as $field) {
    if (isset($data[$field])) {

      // Check if the field is empty but allow numeric values
      if (
        empty($data[$field]) && !is_numeric($data[$field])
      ) {

        // Allow null if field is in $nullables
        if (
          $data[$field] === null && in_array($field, $nullables)
        ) {
          continue;
        }

        // Allow empty values if field is in $emptyables
        if (
          ($data[$field] === '' || $data[$field] === []) &&
          in_array($field, $emptyables)
        ) {
          continue;
        }

        // Field is invalid if it's not allowed to be null or empty
        $isValid = false;
        if ($missingFields !== null) {
          $missingFields[] = $field;
        }
      }
    } else {
      // Field is missing
      if (
        !in_array($field, $nullables) &&
        !in_array($field, $emptyables)
      ) {
        $isValid = false;
        if ($missingFields !== null) {
          $missingFields[] = $field;
        }
      }
    }
  }
  return $isValid;
}



/**
 * Generates a cryptographically secure UUIDv4.
 *
 * @return string The generated UUIDv4.
 */
function generateUUIDv4(): string
{
  $data = random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
  return vsprintf(
    '%s%s-%s-%s-%s-%s%s%s',
    str_split(bin2hex($data), 4)
  );
}



/**
 * Dumps the variable and returns its representation as a string.
 *
 * @param mixed $var The variable to be dumped.
 * @return string The string representation of the dumped variable.
 */
function stringDump(mixed $var): string
{
  ob_start();
  var_dump($var);
  return ob_get_clean();
}



/**
 * Shuffles the characters in a string.
 *
 * @param string $str The string to shuffle.
 * @return string The shuffled string.
 */
function shuffleString(string $str): string
{
  $chars = str_split($str);
  shuffle($chars);
  return implode('', $chars);
}



function base64Charset(): string
{
  return 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
}



/**
 * Generates a token with the specified size.
 *
 * @param int $size The size of the token to generate. Default is 84.
 * @return string The generated token.
 */
function generateToken(int $size = 86): string
{
  //base64Charset [A-Za-z0-9+/] shuffled - 6bit per character
  $possibleChars = shuffleString(
    's7l05JIiBbP1mTjxqAML/68Q49SYUyCowdukGVchaEnvHe2ORZN+Kgzt3FpfWXDr'
  );
  $token = '';
  for ($i = 0; $i < $size; $i++) {
    $token .= $possibleChars[random_int(0, strlen($possibleChars) - 1)];
  }
  return $token;
}



function arrayToString(array $arr): string
{
  $str = '[ ';
  for ($i = 0; $i < count($arr); $i++) {
    $str .= $arr[$i];
    if ($i !== count($arr) - 1) {
      $str .= ', ';
    }
  }
  $str .= ' ]';
  return $str;
}



function getShuffleKey(): string
{
  return getenv('SHUFFLE_KEY') ?: '';
}



function getShuffleIterations(): int
{
  return (int)(getenv('SHUFFLE_ITERATIONS') ?: 0);
}



function padKey(
  string $key,
  int $targetSize,
  int $nDigits,
  int $maxLen = 1024 * 64
) {
  if ($key === '') {
    throw new InvalidArgumentException('Key cannot be empty');
  }

  $glueSize = 1 * $nDigits;
  if ($nDigits & 1 === 1) {
    $glueSize += $nDigits;
  }
  $initialGlueSize = $glueSize;
  $output = $key . "";

  $glueStart = 0;
  $totalTargetSize = $targetSize * $nDigits;

  while (
    strlen($output) < $totalTargetSize &&
    strlen($output) < $maxLen
  ) {
    if ( $glueStart + $glueSize >= strlen( $output ) ){
      $glueStart = 0;
      $glueSize = $initialGlueSize;
    }

    $glue = substr($output, $glueStart, $glueSize);
    $glueStart += $glueSize;
    $glueSize += 2 * $nDigits;
    if ($glueSize > 15 * $nDigits) {
      $glueSize = $initialGlueSize;
    }
    $output .= ($glue . $key);
  }

  return substr(
    $output, 0, $totalTargetSize
  );
}



function getBaseKeyNumericValue(
  string $key,
  int $charIndex,
  ?string $base64charset = null,
  int $nDigits = 1,
  int $base = 64
): int{
  if ( $base64charset === null ){
    $base64charset = base64Charset();
  }

  $index = $charIndex * $nDigits;
  $value = strpos( $base64charset, $key[ $index ] ) * ( $base ** ( $nDigits - 1) );
  for ( $i = 1; $i < $nDigits; ++$i ){
    $pow = $nDigits - $i - 1;
    $value += strpos( $base64charset, $key[ $index + $i ] ) * ( $base ** $pow );
  }
  return $value;
}



function shuffleStringWithPositionsAndShuffleKey(
  string $string,
  array $positions,
  string $shuffleKey,
  bool $reverse = false,
  int $iterations = 1
):string {
  $resultingString = $string . ""; #this patterns forces a copy of the string

  $start = 0;
  $end = strlen($resultingString) - 1;
  $charPos = $start;
  $step = 2;


  if ($reverse) {
    $positions = array_reverse($positions);
  }

  for ($i = $start; $i <= $end; $i += $step) {
    $firstIndex = $positions[$charPos];
    $secondIndex = $positions[$charPos + 1];
    $charPos += $step;

    $tempChar = $resultingString[$firstIndex];
    $resultingString[$firstIndex] = $resultingString[$secondIndex];
    $resultingString[$secondIndex] = $tempChar;
  }

  for ($i = 1; $i < $iterations; $i++) {
    $resultingString = controlledStringShuffle(
      $resultingString,
      $shuffleKey,
      reverse: $reverse,
      skipChecks: true
    );
  }

  return $resultingString;
}


function controlledStringShuffle(
  string $string,
  string $shuffleKey,
  bool $reverse = false,
  bool $skipChecks = false,
  int $iterations = 1
): string {
  $keyLength = 64;
  $stringLength = strlen($string);
  $base64charset = base64Charset();

  if (!$skipChecks) {
    if (strlen($base64charset) != $keyLength) {
      throw new \ErrorException(
        'Base64 charset is not set or is incorrectly set'
      );
    }

    for ($i = 0; $i < strlen($base64charset); ++$i) {
      $char = $base64charset[$i];
      $count = substr_count($base64charset, $char);
      if ($count != 1) {
        throw new \ErrorException(
          "Base64 charset contains repeated characters in it: $char -> $count occurrences."
        );
      }
    }

    if (!$shuffleKey || strlen($shuffleKey) != $keyLength) {
      throw new \ErrorException('shuffleKey is incorrectly set');
    }
    for ($i = 0; $i < $keyLength; $i++) {
      $baseChar = $base64charset[$i];
      if (strpos($shuffleKey, $baseChar) === false) {
        throw new \ErrorException(
          'Either Base64 charset or shuffleKey is incorrectly set'
        );
      }
    }
  }

  $positionsLength = $stringLength;
  if ( $positionsLength & 1 ){
    $positionsLength++;
  }

  $nDigits = (int) ceil( log( $stringLength + 1 ) / log( $keyLength ) );
  if ( $stringLength > $keyLength ){
    $shuffleKey = padKey(
      $shuffleKey,
      $positionsLength,
      $nDigits
    );
  }

  $positions = [];
  for ($i = 0; $i < $positionsLength; $i++) {
    $positions[] = ( getBaseKeyNumericValue(
      $shuffleKey,
      $i,
      $base64charset,
      $nDigits
    ) % $stringLength );
  }

  $resultingString = shuffleStringWithPositionsAndShuffleKey(
    $string,
    $positions,
    $shuffleKey,
    $reverse,
    $iterations
  );

  return $resultingString;
}



function testControlledShuffleV2( int $nTests = 1024 ): void {
  $input1 = "the quick brown fox jumps over the lazy dog";
  $input2 = $input1 . ' ' . $input1;
  $shuffleKey = getShuffleKey();
  $input3 = "";

  while ( strlen( $input3 ) < 4096 ){
    $input3 .= $input1 . ' ';
  }

  for ( $i = 0; $i < $nTests; ++$i ){
    $iter1 = random_int( 1, 20 );
    $iter2 = random_int( 1, 20 );
    $iter3 = random_int( 1, 20 );

    $enc1 = controlledStringShuffle( $input1, $shuffleKey, iterations: $iter1 );
    $enc2 = controlledStringShuffle( $input2, $shuffleKey, iterations: $iter2 );
    $enc3 = controlledStringShuffle( $input3, $shuffleKey, iterations: $iter3 );

    $dec1 = controlledStringShuffle( $enc1, $shuffleKey, reverse: true, iterations: $iter1 );
    $dec2 = controlledStringShuffle( $enc2, $shuffleKey, reverse: true, iterations: $iter2 );
    $dec3 = controlledStringShuffle( $enc3, $shuffleKey, reverse: true, iterations: $iter3 );

    if ( $dec1 !== $input1 ){
      throw new \ErrorException( "Iter $i | its $iter1 Test failed for input1: $dec1 != $input1\n" );
    }
    if ( $dec2 !== $input2 ){
      throw new \ErrorException( "Iter $i | its $iter2 Test failed for input2: $dec2 != $input2\n" );
    }
    if ( $dec3 !== $input3 ){
      throw new \ErrorException( "Iter $i | its $iter3 Test failed for input3: $dec3 != $input3\n" );
    }
  }
}



function configureCookies(){
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => getenv('APP_ENV') === 'production', // NOT in local dev (docker runs plain HTTP on :8080)
    'samesite' => 'Lax',
  ]);
}



function csrfToken(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}



function csrfVerify(?string $token): bool {
  return is_string($token)
    && !empty($_SESSION['csrf_token'])
    && hash_equals($_SESSION['csrf_token'], $token);
}


namespace utils\user;

use database\UserRole;
use database\User;

/**
 * Retrieves the currently logged-in user from the session.
 *
 * @return User|null The user object if logged in, or null.
 */
function user(): ?User
{
  if (session_status() === PHP_SESSION_NONE) {
    \utils\configureCookies();
    session_start();
  }
  if (isset($_SESSION['user']) && $_SESSION['user'] instanceof User) {
    return $_SESSION['user'];
  }
  if (isset($_SESSION['user_uuid']) && is_string($_SESSION['user_uuid'])) {
    $user = \database\UserEntity::instance()->getByUUID($_SESSION['user_uuid']);
    if ($user) {
      $_SESSION['user'] = $user;
      return $user;
    }
  }
  return null;
}

/**
 * Checks if the current user is logged in.
 *
 * @return bool True if a user is logged in, false otherwise.
 */
function isUserLoggedIn(): bool
{
  return user() !== null;
}


/**
 * Retrieves the role of the currently logged-in user.
 *
 * @return string|null The user's role if logged in, or null.
 */
function userRole(): ?UserRole
{
  $user = user();
  if ($user) {
    return $user->getRole();
  }
  return null;
}


/**
 * Checks if the current user is the owner.
 *
 * @return bool True if the user is the owner, false otherwise.
 */
function userIsOwner(): bool
{
  return userRole() === UserRole::OWNER;
}



/**
 * Checks if the current user is an admin.
 *
 * @return bool True if the user is an admin, false otherwise.
 */
function userIsAdmin(): bool
{
  return userRole() === UserRole::ADMIN;
}



/**
 * Checks if the current user is an editor.
 *
 * @return bool True if the user is an editor, false otherwise.
 */
function userIsEditor(): bool
{
  return userRole() === UserRole::EDITOR;
}



/**
 * Checks if the current user has edit clearance (owner, admin, or editor).
 *
 * @return bool True if the user has edit clearance, false otherwise.
 */
function userHasEditClearance(): bool
{
  return userIsOwner() || userIsAdmin() || userIsEditor();
}

namespace utils\project;

function getProjectCategoryText( string $category ){
  $categories = \database\ProjectEntity::$validProjectCategories;
  $categoryText = $categories[$category] ?? $category;

  return $categoryText;
}


namespace utils\endpoints;

function validateDateParameter(
  string $parameterName,
  array $body,
  ?callable $onError,
  bool $required = true,
  ?string $default = null
){
  $value = $default;
  if ( isset( $body[ $parameterName ] ) ){
    $value = trim( $body[ $parameterName ] );
    if ( strlen( $value ) > 32 ){
      if ( $onError ) $onError();
      http_response_code(413);
      exit ( "$parameterName too large." );
    }
    if ( $value === '' ){
      if ( $required ){
        if ( $onError ) $onError();
        http_response_code(400);
        exit ( "Invalid empty $parameterName input." );
      }
      return $default;
    } else {
      $parsedDate = \DateTime::createFromFormat( 'Y-m-d', $value );
      if ( $parsedDate === false || $parsedDate->format( 'Y-m-d' ) !== $value ){
        if ( $onError ) $onError();
        http_response_code( 400 );
        exit ( "Invalid $parameterName input \"" . $body[ $parameterName ] . '". Expected format is yyyy-mm-dd.' );
      }
      $value = $parsedDate->format( 'Y-m-d' );
    }
  } else {
    if ( $required ){
      if ( $onError ) $onError();
      http_response_code(400);
      exit ( "Missing $parameterName parameter." );
    }
    $value = $default;
  }
  return $value;
}

function validateStringParameter(
  string $parameterName,
  array $body,
  ?bool $checkEmpty = true,
  ?int $maxSize = -1,
  ?array $validInputs = null,
  ?bool $strict = true,
  ?bool $required = true,
  ?string $default = null,
  ?callable $onError = null,
  ?int $filter = null,
){
  $value = null;
  if ( isset( $body[ $parameterName ] ) ){
    $value = trim( (string) $body[ $parameterName ] );
    if ( $filter !== null ){
      $value = filter_var( $value, $filter );
    }
    if ( $maxSize === null ) $maxSize = -1;
    if ( $maxSize > 0 && strlen( $value ) > $maxSize ){
      if ( $onError ) $onError();
      http_response_code(413);
      exit ( "$parameterName too large." );
    }
    if ( $strict === null ) $strict = true;
    if ( $validInputs !== null && !in_array( $value, $validInputs, $strict ) ){
      if ( $onError ) $onError();
      http_response_code( 400 );
      exit ( "Invalid $parameterName input \"" . $body[ $parameterName ] . '".' );
    }
    if ( $checkEmpty === null ) $checkEmpty = true;
    if ( $checkEmpty && $value === '' ){
      if ( $onError ) $onError();
      http_response_code( 400 );
      exit ( "Invalid empty $parameterName input" );
    }
  } else {
    if ( $required === null ) $required = true;
    if ( $required ){
      if ( $onError ) $onError();
      http_response_code(400);
      exit ( "Missing $parameterName parameter." );
    } else {
      $value = $default;
    }
  }
  return $value;
}


function validateNumericParameter(
  string $parameterName,
  array $body,
  ?callable $onError,
  ?bool $checkIsFinite = true,
  ?bool $required = true,
  int|float|null $default = null
){
  if ( isset( $body[ $parameterName ] ) ){
    $value = trim( $body[ $parameterName ] );
    if ( $checkIsFinite === null ) $checkIsFinite = true;
    if ( !is_numeric( $value ) || ( $checkIsFinite && !is_finite( $value + 0 ) ) ){
      if ( $onError ) $onError();
      http_response_code( 400 );
      exit ( "Invalid $parameterName input \"" . $body[ $parameterName ] . '".' );
    }
  } else {
    if ( $required === null ) $required = true;
    if ( $required ){
      if ( $onError ) $onError();
      http_response_code(400);
      exit ( "Missing $parameterName parameter." );
    } else {
      $value = $default;
    }
  }
  return $value;
}


function handleImageUpload(
  $fileParamName = 'file',
  ?int $maxSize = 10 * 1024 * 1024,
  ?array $allowedTypes = \config\ALLOWED_IMAGE_UPLOAD_TYPES,
  ?string $destination_folder = \config\IMAGES_UPLOAD_FOLDER,
  ?callable $onError = null,
): ?array {
  if ( !isset( $_FILES[ $fileParamName ] ) ) return null;

  $file = $_FILES[ $fileParamName ];
  if ( $file['error'] !== UPLOAD_ERR_OK ){
    if ( $onError ) $onError();
    http_response_code( 400 );
    exit ( 'Invalid file upload.' );
  }

  // Size limit (e.g. 10 MB)
  if ( $maxSize === null ) $maxSize = 10 * 1024 * 1024;
  if ( $file['size'] > $maxSize ){
    if ( $onError ) $onError();
    http_response_code( 413 );
    exit ( 'File too large.' );
  }

  // Validate it's actually an image (not just trusting the MIME/filename)
  $finfo  = new \finfo( FILEINFO_MIME_TYPE );
  $mime   = $finfo->file( $file['tmp_name'] );
  if ( $allowedTypes === null ) $allowedTypes = \config\ALLOWED_IMAGE_UPLOAD_TYPES;
  if ( !in_array( $mime, $allowedTypes, true ) ){
    if ( $onError ) $onError();
    http_response_code( 400 );
    exit ( "Invalid file type; $mime" );
  }

  $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
  $newPath   = bin2hex( random_bytes( 16 ) ) . '.' . $extension;

  if ( $destination_folder === null ) $destination_folder = \config\IMAGES_UPLOAD_FOLDER;

  $destDir   = $_SERVER['DOCUMENT_ROOT'] . $destination_folder;
  if ( !is_dir( $destDir ) && !mkdir( $destDir, 0775, true ) ){
    if ( $onError ) $onError();
    http_response_code( 500 );
    exit ( 'Failed to create upload directory.' );
  }
  $destPath  = $destDir . $newPath;

  if ( !move_uploaded_file( $file['tmp_name'], $destPath ) ){
    if ( $onError ) $onError();
    http_response_code( 500 );
    exit ( 'Failed to store file.' );
  }

  // Read dimensions of the stored file
  $size = getimagesize( $destPath );
  if ( $size === false ){
    @unlink( $destPath );
    if ( $onError ) $onError();
    http_response_code( 400 );
    exit ( 'File is not a valid image.' );
  }

  $path = $destination_folder . $newPath;
  $width = $size[0];
  $height = $size[1];

  return [
    'path'          => $path,
    'width'         => $width,
    'height'        => $height,
    'savedFilePath' => $destPath
  ];
}


namespace utils\database;

/**
 * Builds a SQL "IN (?, ?, ...)" clause from a list of integer ids.
 *
 * Returns the placeholder list, the mysqli bind types string, and the
 * parameter values. When the input list is empty it returns a safe
 * "IN (0)" clause so callers can still issue a no-op query.
 *
 * @param int[] $ids The integer ids to bind.
 * @return array{placeholders:string, types:string, params:int[]}
 */
function buildIntIdsInParams( array $ids ): array
{
  $ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
  $ids = array_filter( $ids, fn( $id ) => $id > 0 );

  if ( empty( $ids ) ){
    return [ 'placeholders' => '0', 'types' => '', 'params' => [] ];
  }

  return [
    'placeholders' => implode( ',', array_fill( 0, count( $ids ), '?' ) ),
    'types'        => str_repeat( 'i', count( $ids ) ),
    'params'       => $ids,
  ];
}
