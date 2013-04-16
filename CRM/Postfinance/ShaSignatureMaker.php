<?php

class CRM_Postfinance_ShaSignatureMaker {

  protected $secret;
  protected $keys;
  protected $algo;

  /**
   * @param string $secret
   *   String to be inserted between the params as a salt, before hash().
   * @param array $keys
   *   Keys of the param that should be used for hash calculation.
   * @param string $algo
   *   The algorithm to use - e.g. "md5", "sha1", "sha512" etc.
   *   Typically this is called with "sha512".
   */
  function __construct($secret, array $keys, $algo) {
    $this->secret = $secret;
    $this->keys = array_combine($keys, $keys);
    $this->algo = $algo;
  }

  /**
   * Calculate the hash for the given params.
   *
   * @param array $params
   *   Url params, before they are encoded.
   *
   * @return string
   *   Hash for the given params.
   */
  function makeSignature(array $params) {
    $str = '';
    $params = $this->keysToUpper($params);
    ksort($params);
    foreach ($params as $k => $v) {
      if (isset($this->keys[$k]) && isset($v) && '' !== $v) {
        $str .= $k . '=' . $v . $this->secret;
      }
    }
    dpm($str);
    return hash($this->algo, $str);
  }

  protected function keysToUpper(array $arr) {
    $result = array();
    foreach ($arr as $k => $v) {
      $result[strtoupper($k)] = $v;
    }
    return $result;
  }
}
