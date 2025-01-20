<?php

namespace app\components;

use app\models\Heartbeat;
use yii\base\Component;

class HeartbeatService extends Component
{
    private $heartbeat;

    public function __construct()
    {
        parent::__construct();
        $this->heartbeat = new Heartbeat();
    }

    /**
     * @param string $serviceName
     * @param string $status
     * @return bool
     */
    public function addHeartbeat($serviceName, $status)
    {
        $this->heartbeat->service_name = $serviceName;
        $this->heartbeat->last_run_at = date('Y-m-d H:i:s');
        $this->heartbeat->status = $status;

        return $this->heartbeat->save();
    }
}
