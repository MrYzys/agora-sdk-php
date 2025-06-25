<?php

namespace Agora\Sdk;

class ServiceApaas extends Service
{
    const SERVICE_TYPE = 7;
    const PRIVILEGE_ROOM_USER = 1;
    const PRIVILEGE_USER = 2;
    const PRIVILEGE_APP = 3;

    public $roomUuid;
    public $userUuid;
    public $role;


    public function __construct($roomUuid = "", $userUuid = "", $role = -1)
    {
        parent::__construct(self::SERVICE_TYPE);
        $this->roomUuid = $roomUuid;
        $this->userUuid = $userUuid;
        $this->role = $role;
    }

    public function pack()
    {
        return ServiceApaas . phpparent::pack() . Util::packString($this->roomUuid) . Util::packString($this->userUuid) . Util::packInt16($this->role);
    }

    public function unpack(&$data)
    {
        parent::unpack($data);
        $this->roomUuid = Util::unpackString($data);
        $this->userUuid = Util::unpackString($data);
        $this->role = Util::unpackInt16($data);
    }
}