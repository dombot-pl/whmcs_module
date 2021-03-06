<?php
namespace Tests\Odr;

use Tests\UnitTestCase;

class OdrRenewDomainTest extends UnitTestCase
{
    public function testNotLoggedIn()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$failure',
                'api_secret' => 'secret$success',
                'token'      => 'public$success',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('status' => \Odr_Whmcs::STATUS_ERROR, 'error' => 'Can\'t login, reason - Forced error'), odr_RenewDomain($data));
    }

    public function testError()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$failure',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Following error occurred: Forced error'), odr_RenewDomain($data));
    }

    public function testException()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$thrown',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Following error occurred: ' . $module::MESSAGE_CURL_ERROR_FOUND), odr_RenewDomain($data));
    }

    public function testSuccess()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$success',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('status' => 'Domain Renewed. Please be aware, that this operation only changes the date in WHMCS and not in ODR. ODR will renew domain on it\'s own and you can always obtain correct date using Sync'), odr_RenewDomain($data));
    }

    public function testSuccessAutorenewOff()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$suautorenewoff',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Renewal is impossible'), odr_RenewDomain($data));
    }

    public function testSuccessPending()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$supending',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Renewal is impossible, invalid domain status'), odr_RenewDomain($data));
    }

    public function testSuccessDeleted()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$sudeleted',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Renewal is impossible, invalid domain status'), odr_RenewDomain($data));
    }

    public function testSuccessQuarantine()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$suquarantine',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('status' => 'Domain Reactivated'), odr_RenewDomain($data));
    }

    public function testSuccessQuarantineNo()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$suquarantine',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'false.nl',
            'sld'              => 'false',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Reactivation is impossible'), odr_RenewDomain($data));
    }

    public function testSuccessQuarantineFalse()
    {
        $module = $this->getModule();

        $module->setConfig(
            array(
                'api_key'    => 'public$success',
                'api_secret' => 'secret$success',
                'token'      => 'token$suquarantinefalse',
            )
        );

        \Odr_Whmcs::$module = $module;

        $data = array(
            'whmcsVersion'     => self::WHMCS_VERSION,
            'Username'         => '',
            'OdrApiKey'        => 'public$live',
            'OdrApiSecret'     => 'secret$live',
            'OdrTestApiKey'    => 'public$test',
            'OdrTestApiSecret' => 'secret$test',
            'OdrTestmode'      => 'on',
            'domainObj'        => array(),
            'domainid'         => '1',
            'domainname'       => 'test.nl',
            'sld'              => 'test',
            'tld'              => 'nl',
            'registrar'        => 'odr',
            'firstname'        => 'A',
            'lastname'         => 'B',
            'companyname'      => 'C',
        );

        self::assertEquals(array('error' => 'Following error occurred: Forced error'), odr_RenewDomain($data));
    }
}