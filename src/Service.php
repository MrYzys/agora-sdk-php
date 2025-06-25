<?php
namespace Agora\Sdk;

class Service
{
    public $type;
    public $privileges;

    public function __construct($serviceType)
    {
        $this->type = $serviceType;
    }

    public function addPrivilege($privilege, $expire)
    {
        $this->privileges[$privilege] = $expire;
    }

    public function getServiceType()
    {
        return $this->type;
    }

    public function pack()
    {
        return Util::packUint16($this->type) . Util::packMapUint32($this->privileges);
    }

    public function unpack(&$data)
    {
        $this->privileges = Util::unpackMapUint32($data);
    }
}