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
   *   The algorithm to use - e.g. md5, sha1, etc.
   */
  function __construct($secret, array $keys, $algo = 'sha1') {
    $this->secret = $secret;
    $this->keys = $keys;
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
    dpm($params, 'makeSignature($params)');
    $str = '';
    foreach ($this->keys as $key) {
      if (isset($params[$key]) && '' !== $params[$key]) {
        $str .= $key . '=' . $params[$key] . $this->secret;
      }
    }
    dpm($str, '$str before hash()');
    return hash($this->algo, $str);
  }
}
