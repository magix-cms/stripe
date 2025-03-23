<?php
class plugins_stripe_db
{
    /**
     * @param $config
     * @param bool $params
     * @return mixed|null
     * @throws Exception
     */
    /**
     * @var debug_logger $logger
     */
    protected debug_logger $logger;

    /**
     * @param array $config
     * @param array $params
     * @return array|bool
     */
    public function fetchData(array $config, array $params = []) {
        if ($config['context'] === 'all') {
            switch ($config['type']) {
                case 'data':
                    $query = 'SELECT mo.* FROM mc_stripe AS mo';
                    break;
                default:
                    return false;
            }

            try {
                return component_routing_db::layer()->fetchAll($query, $params);
            }
            catch (Exception $e) {
                if(!isset($this->logger)) $this->logger = new debug_logger(MP_LOG_DIR);
                $this->logger->log('statement','db',$e->getMessage(),$this->logger::LOG_MONTH);
            }
        }
        elseif ($config['context'] === 'one') {
            switch ($config['type']) {
                case 'root':
                    $query = 'SELECT * FROM mc_stripe ORDER BY id_stripe DESC LIMIT 0,1';
                    break;
                case 'history':
                    $query = 'SELECT * FROM mc_stripe_history WHERE order_h = :order_h';
                    break;
                case 'lastHistory':
                    $query = 'SELECT * FROM mc_stripe_history WHERE session_key_cart = :session_key_cart ORDER BY id_stripe_h DESC LIMIT 0,1';
                    break;
                default:
                    return false;
            }

            try {
                return component_routing_db::layer()->fetch($query, $params);
            }
            catch (Exception $e) {
                if(!isset($this->logger)) $this->logger = new debug_logger(MP_LOG_DIR);
                $this->logger->log('statement','db',$e->getMessage(),$this->logger::LOG_MONTH);
            }
        }
        return false;
    }
    /**
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function insert(string $type, array $params = []): bool {
        switch ($type) {
            case 'config':

                $query = 'INSERT INTO mc_stripe (apikey,endpointkey)
                VALUE(:apikey,:endpointkey)';

                break;
            case 'history':

                $query = 'INSERT INTO mc_stripe_history (session_key_cart,order_h,event_h,status_h)
                VALUE(:session_key_cart,:order_h,:event_h,:status_h)';

                break;
            default:
                return false;
        }

        try {
            component_routing_db::layer()->insert($query,$params);
            return true;
        }
        catch (Exception $e) {
            if(!isset($this->logger)) $this->logger = new debug_logger(MP_LOG_DIR);
            $this->logger->log('statement','db',$e->getMessage(),$this->logger::LOG_MONTH);
            return false;
        }

    }

    /**
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function update(string $type, array $params = []): bool {
        switch ($type) {
            case 'config':
                $query = 'UPDATE mc_stripe
                    SET 
                        apikey=:apikey,
                        endpointkey=:endpointkey
                    WHERE id_stripe=:id';
                break;
            default:
                return false;
        }

        try {
            component_routing_db::layer()->update($query,$params);
            return true;
        }
        catch (Exception $e) {
            if(!isset($this->logger)) $this->logger = new debug_logger(MP_LOG_DIR);
            $this->logger->log('statement','db',$e->getMessage(),$this->logger::LOG_MONTH);
            return false;
        }
    }
}
?>