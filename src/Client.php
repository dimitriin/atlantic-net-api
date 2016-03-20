<?php
/**
 * Created by PhpStorm.
 * User: nasedkin
 * Date: 17.03.16
 * Time: 14:15
 */
namespace Dimitriin\AtlanticNet\API;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

class Client {

    const API_HOST = "https://cloudapi.atlantic.net";
    const API_VERSION = "2010-12-30";

    const PLATFORM_LINUX = "linux";
    const PLATFORM_WINDOWS = "windows";

    const PLAN_S   = "S";
    const PLAN_XS  = "XS";
    const PLAN_M   = "M";
    const PLAN_L   = "L";
    const PLAN_XL  = "XL";
    const PLAN_2XL = "2XL";
    const PLAN_3XL = "3XL";
    const PLAN_4XL = "4XL";

    const INSTANCE_STATUS_AWAITING_CREATION = 'AWAITING_CREATION';
    const INSTANCE_STATUS_CREATING = 'CREATING';
    const INSTANCE_STATUS_FAILED = 'FAILED';
    const INSTANCE_STATUS_REPROVISIONING = 'REPROVISIONING';
    const INSTANCE_STATUS_RESETTINGPWD = 'RESETTINGPWD';
    const INSTANCE_STATUS_RESTARTING = 'RESTARTING';
    const INSTANCE_STATUS_RUNNING = 'RUNNING';
    const INSTANCE_STATUS_STOPPED = 'STOPPED';
    const INSTANCE_STATUS_QUEUED = 'QUEUED';
    const INSTANCE_STATUS_REMOVING = 'REMOVING';
    const INSTANCE_STATUS_REMOVED = 'REMOVED';
    const INSTANCE_STATUS_RESIZINGSERVER = 'RESIZINGSERVER';
    const INSTANCE_STATUS_SUSPENDING = 'SUSPENDING';
    const INSTANCE_STATUS_SUSPENDED = 'SUSPENDED';

    const LOCATION_USEAST1    = "USEAST1"; //Orlando
    const LOCATION_USEAST2    = "USEAST2"; //New-York
    const LOCATION_USCENTRAL1 = "USCENTRAL1"; //Dallas
    const LOCATION_USWEST1    = "USWEST1"; //San Francisco
    const LOCATION_CAEAST1    = "CAEAST1"; //Toronto
    const LOCATION_EUWEST1    = "EUWEST1"; //London

    const WAIT_TIMEOUT = 300;
    const TICK_TIMEOUT = 10;

    const REBOOT_TYPE_SOFT = 'soft';
    const REBOOT_TYPE_HARD = 'hard';

    const ARCH_X86_64 = 'x86_64';
    const ARCH_I386 = 'i386';
    const ARCH_AMD64 = 'amd64';

    protected $apiKey;
    protected $apiPrivateKey;
    protected $format = "json";
    protected $httpClient;

    public function __construct($apiKey, $apiPrivateKey) {
        $this->apiKey = $apiKey;
        $this->apiPrivateKey = $apiPrivateKey;

        $this->httpClient = new \GuzzleHttp\Client(array(
            'base_uri' => self::API_HOST,
            'timeout'  => 10.0,
        ));
    }

    /**
     * @return array
     */
    public function locationList() {
        return array(
            self::LOCATION_USEAST1    => 'Orlando',
            self::LOCATION_USEAST2    => 'New-York',
            self::LOCATION_USCENTRAL1 => 'Dallas',
            self::LOCATION_USWEST1    => 'San Francisco',
            self::LOCATION_CAEAST1    => 'Toronto',
            self::LOCATION_EUWEST1    => 'London',
        );
    }

    /**
     * @param $instanceId
     * @return array|null
     */
    public function instance($instanceId) {
        $data = $this->sendAPIRequest('describe-instance', array(
            'instanceid' => $instanceId,
        ));
        if( isset($data['instanceSet']) && is_array($data['instanceSet']) ) {
            return reset($data['instanceSet']);
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function instanceList() {
        $data = $this->sendAPIRequest('list-instances');
        if( isset($data['instancesSet']) && is_array($data['instancesSet']) ) {
            return $data['instancesSet'];
        } else {
            return array();
        }
    }

    /**
     * @param string $serverName
     * @param string $imageId
     * @param string $planName
     * @param string $location
     * @param null|bool $enableBackup
     * @param null|string $cloneImage
     * @param null|integer $serverQty
     * @param null|string $keyId
     * @return array|null
     */
    public function runInstance(
        $serverName,
        $imageId,
        $planName,
        $location,
        $enableBackup = null,
        $cloneImage = null,
        $serverQty = null,
        $keyId = null
    ) {
        $data = $this->sendAPIRequest('run-instance', array(
            'servername'    => $serverName,
            'imageid'       => $imageId,
            'planname'      => $planName,
            'vm_location'   => $location,
            'enablebackup'  => $enableBackup,
            'cloneimage'    => $cloneImage,
            'serverqty'     => $serverQty,
            'keyid'         => $keyId,
        ));

        if(isset($data['instancesSet']) && is_array($data['instancesSet'])) {
            return reset($data['instancesSet']);
        } else {
            return null;
        }
    }

    /**
     * @param string $serverName
     * @param string $imageId
     * @param string $planName
     * @param string $location
     * @param null|bool $enableBackup
     * @param null|string $cloneImage
     * @param null|integer $serverQty
     * @param null|string $keyId
     * @return array|null
     */
    public function runInstanceSync(
        $serverName,
        $imageId,
        $planName,
        $location,
        $enableBackup = null,
        $cloneImage = null,
        $serverQty = null,
        $keyId = null
    ) {
        $resp = $this->runInstance(
            $serverName,
            $imageId,
            $planName,
            $location,
            $enableBackup,
            $cloneImage,
            $serverQty,
            $keyId
        );

        if( $resp && isset($resp['instanceid']) ) {
            $instanceId = $resp['instanceid'];
            $stop = time() + self::WAIT_TIMEOUT;
            while( time() <= $stop) {
                sleep(self::TICK_TIMEOUT);
                $data = $this->instance($instanceId);
                if(isset($data['vm_status'])) {
                    switch($data['vm_status']) {
                        case self::INSTANCE_STATUS_RUNNING:
                            return $resp;
                        break;
                        case self::INSTANCE_STATUS_FAILED:
                            return null;
                        break;
                    }
                } else {
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * @param string $instanceId
     * @param null|string $type
     * @return null|array
     */
    public function rebootInstance($instanceId, $type = null) {
        $data = $this->sendAPIRequest('reboot-instance',array('instanceid' => $instanceId,'reboottype' => $type));
        if(isset($data['return'])) {
            return $data['return'];
        } else {
            return null;
        }
    }

    /**
     * @param string $instanceId
     * @return null|array
     */
    public function terminateInstance($instanceId) {
        $data = $this->sendAPIRequest('terminate-instance',array('instanceid' => $instanceId));
        if(isset($data['instancesSet']) && is_array($data['instancesSet'])) {
            return reset($data['instancesSet']);
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function terminateAll() {
        $result = array();
        $list = $this->instanceList();
        foreach( $list as $instance ) {
            $result[$instance['InstanceId']] = $this->terminateInstance($instance['InstanceId']);
        }
        return $result;
    }

    /**
     * @return array
     */
    public function sshKeyList() {
        $data = $this->sendAPIRequest('list-sshkeys');
        if( isset($data['KeysSet']) && is_array($data['KeysSet']) ) {
            return $data['KeysSet'];
        } else {
            return array();
        }
    }

    /**
     * @param string $planName
     * @param string $platform
     * @return null
     */
    public function plan( $planName, $platform ) {
        $data = $this->planList($planName, $platform);
        foreach( $data as $plan ) {
            return $plan;
        }
        return null;
    }

    /**
     * @param null|string $planName
     * @param null|string $platform
     * @return array
     */
    public function planList( $planName = null, $platform = null ) {
        $data = $this->sendAPIRequest('describe-plan', array(
            'plan_name' => $planName,
            'platform'  => $platform,
        ));

        if( isset($data['plans']) && is_array($data['plans']) ) {
            return $data['plans'];
        } else {
            return array();
        }
    }

    /**
     * @param $imageId
     * @return null|array
     */
    public function image($imageId) {
        $list = $this->imageList($imageId);
        foreach( $list as $image ) {
            return $image;
        }
        return null;
    }

    /**
     * @param string $label
     * @param null|string $version
     * @param null|string $arch
     * @return null|array
     */
    public function imageByName($label, $version = null, $arch = null) {
        $list = $this->imageList();
        foreach( $list as $image) {
            if(
                (strpos(strtolower($image['displayname']), strtolower($label)) === 0) &&
                ( is_null($arch) || ($image['architecture'] == $arch)) &&
                ( is_null($version) || (isset($image['version']) && ($image['version'] == $version)))
            ) {
                return $image;
            }
        }
        return null;
    }

    /**
     * @param null|string $imageId
     * @return array
     */
    public function imageList( $imageId = null ) {
        $data = $this->sendAPIRequest('describe-image', array('imageid' => $imageId));
        if( isset($data['imagesset']) && is_array($data['imagesset']) ) {
            return $data['imagesset'];
        } else {
          return array();
        }
    }

    /**
     * @param $action
     * @param array $params
     * @return bool|array
     */
    protected function sendAPIRequest($action, array $params = array()) {
        $query = array_merge(array(
            'Version' => self::API_VERSION,
            'Format'  => $this->format,
            'Action'  => $action,
        ), $this->generateAuthParams(), $this->filterParams($params));

        $response = $this->httpClient->request("GET", null, array(
            'query' => $query,
        ));

        if( $response->getStatusCode() == 200 ) {
            $body = (string) $response->getBody();

            $responseKey =  $action . "response";
            if(
                ($data = json_decode($body,1)) &&
                isset($data[$responseKey])
            ) {
                return $data[$responseKey];
            }
        }
        return false;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function filterParams(array $params) {
        $cleared = array();
        foreach( $params as $key => $value ) {
            if( !is_null($value) ) {
                if(is_bool($value)) {
                    $value = $value ? 'Y' : 'N';
                }
                $cleared[$key] = $value;
            }
        }
        return $cleared;
    }

    /**
     * @return array
     */
    protected function generateAuthParams() {
        $timestamp = time();
        $guid = $this->generateGuid();
        $sign = base64_encode(hash_hmac('sha256', $timestamp . $guid, $this->apiPrivateKey, true));
        return array(
            'ACSAccessKeyId'    => $this->apiKey,
            'Timestamp'         => $timestamp,
            'Rndguid'           => $guid,
            'Signature'         => $sign,
        );
    }


    /**
     * @return string
     */
    function generateGuid() {
        return sprintf(
            '%04X%04X%04X%04X%04X%04X%04X%04X%04X',
            mt_rand(0, 65535), mt_rand(16384, 20479),
            mt_rand(0, 65535), mt_rand(16384, 20479),
            mt_rand(32768, 49151), mt_rand(0, 65535),
            mt_rand(0, 65535), mt_rand(0, 65535),
            mt_rand(32768, 49151));
    }

    /**
     * @param string $ip
     * @param string $username
     * @param string $password
     * @param string $pubKey
     * @return null|bool
     */
    public function addAuthorizedKey($ip, $username, $password, $pubKey) {
        $ssh = new SSH2($ip);
        if ($ssh->login($username, $password)) {
            $r = $ssh->exec('mkdir -p ~/.ssh && echo "' . $pubKey . '" >> ~/.ssh/authorized_keys');
            if( !$r ) {
                return true;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function generateSshKeyPair() {
        $rsa = new RSA();
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
        $keys = $rsa->createKey();

        return array(
            'privateKey' => $keys['privatekey'],
            'publicKey'  => $keys['publickey'],
        );
    }

    /**
     * @param string $ip
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    public function pingInstance($ip, $port = 22, $timeout = 3) {
        if ($fp = @fsockopen($ip, $port, $errCode, $errStr, $timeout)) {
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function waitSshConnection($ip) {
        $stop = time() + self::WAIT_TIMEOUT;
        while( time() <= $stop) {
            if( $this->pingInstance($ip) ) {
                return true;
            }
            sleep(self::TICK_TIMEOUT);
        }
        return false;
    }
}