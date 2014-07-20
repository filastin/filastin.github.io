<?php

require 'configuration.php';
require 'medoo.min.php';
require 'Toro.php';

class App {
  private static $db;
  private static function getDb() {
    global $configuration;
    if (is_null(self::$db)) {
      self::$db = new medoo($configuration['db']);
    }
    return self::$db;
  }
  public static function escape($inp) { // source: http://fr2.php.net/mysql_real_escape_string#101248
    if(is_array($inp))
      return array_map(__METHOD__, $inp);
    if(!empty($inp) && is_string($inp)) {
      return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }
    return $inp;
  } 
  public static function response($data, $status=200) {
    if (is_string($data))
	  $data = array('message' => $data);
    return json_encode(array('status' => $status, 'data' => $data));
  }
  public static function signaturesNb() {
    return self::getDb()->count(TBL_SIGNATURES);
  }
  public static function signaturesNew($initials, $email) {
    return self::getDb()->insert(TBL_SIGNATURES, array(
      'initials' => self::escape($initials), 
      'email' => self::escape(strtoupper($email))
    ));
  }
  public static function signatureExists($email) {
    return self::getDb()->has(TBL_SIGNATURES, array('email' => self::escape($email)));
  }
  function get() {
    if (isset($_GET['nb'])) {
      echo $this->response(array('nb' => $this->signaturesNb()));
      return;
    }
    include 'page.html';
  }
  function post() {
    $err = array();
    if (!isset($_POST['initials']{1}))
      $err[] = 'Les initiales doivent êtres au minimum de longueur 2';
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
      $err[] = 'L\'adresse email est invalide';
    if (count($err) === 0) {
      if ($this->signatureExists($_POST['email'])) {
        echo $this->response('Votre signature a déjà été prise en compte. Merci!');
        return;
      }
      $id = $this->signaturesNew($_POST['initials'], $_POST['email']);
      if (!($id > 0))
        $err[] = 'Erreur lors de l\'enregistrement de la signature';
    }
    if (count($err) > 0) {
      echo $this->response($err, 400);
      return;
    }
    echo $this->response('Merci pour votre signature!', 201);
  }
}

ToroHook::add('404', function() { echo App::response('REST Service not found!', 404); });
Toro::serve(array('/' => 'App'));
