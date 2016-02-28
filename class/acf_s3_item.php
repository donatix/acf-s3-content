<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 16-02-28
 * Time: 20:07
 */

class acf_s3_item {

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var string
     */
    private $key;

    /**
     * acf_s3_item constructor.
     * @param string $bucket
     * @param string $key
     */
    function __construct($bucket, $key) {
        $this->bucket = $bucket;
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getBucket() {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return sprintf('https://%s.s3.amazonaws.com/%s', $this->bucket, $this->key);
    }

    public function getBasename() {
        return basename($this->key);
    }

}
