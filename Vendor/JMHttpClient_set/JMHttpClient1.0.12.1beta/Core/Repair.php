<?php
/**
 * Fault host check.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace JMHttpClient\Core;

/**
 * Falut hosts.
 */
class Repair
{
    static protected $instance = null;
    protected $semid = null;
    protected $shmid = null;
    protected $shmsize = 20480;

    /**
     * Get a instance.
     *
     * @return \JMHttpClient\Core\Repair
     */
    static public function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->semid = sem_get(ftok(__FILE__, '.'));
        $this->shmid = shm_attach(ftok(__FILE__, '..'), $this->shmsize);
    }

    /**
     * Destructor.
     */
    protected function __destruct()
    {
        shm_detach(ftok(__FILE__, '..'));
    }

    /**
     * Get fault host.
     *
     * @return array
     */
    public function getFaultHosts()
    {
        $faultHosts = array();
        if (sem_acquire($this->semid) && shm_has_var($this->shmid, crc32('fault_hosts'))) {
            $faultHosts = shm_get_var($this->shmid, crc32('fault_hosts'));
            sem_release($this->semid);
        }

        return $faultHosts;
    }

    /**
     * Recovery all.
     *
     * @return void
     */
    public function recoveryAll()
    {
        if (sem_acquire($this->semid)) {
            shm_put_var($this->shmid, crc32('fault_hosts'), array());
            sem_release($this->semid);
        }
    }

    /**
     * Recovery host.
     *
     * @param boolean $recoveryInterval Recovery interval(s).
     *
     * @return void
     */
    public function recoveryFalutHost($recoveryInterval = 60)
    {
        if (sem_acquire($this->semid) && shm_has_var($this->shmid, crc32('fault_hosts'))) {
            $faultHosts = shm_get_var($this->shmid, crc32('fault_hosts'));

            $recovery = array();
            foreach ($faultHosts as $host => $timestamp) {
                if (time() - $timestamp >= $recoveryInterval) {
                    $recovery[] = $host;
                }
            }

            foreach ($recovery as $host) {
                unset($faultHosts[$host]);
            }

            if ($recovery) {
                shm_put_var($this->shmid, crc32('fault_hosts'), $faultHosts);
            }

            sem_release($this->semid);
        }
    }

    /**
     * Fault host.
     *
     * @param string $host Host.
     *
     * @return void
     */
    public function test($host)
    {
        if (sem_acquire($this->semid)) {
            if (shm_has_var($this->shmid, crc32('fault_hosts'))) {
                $faultHosts = shm_get_var($this->shmid, crc32('fault_hosts'));
                if (! isset($faultHosts[$host])) {
                    $faultHosts[$host] = time();
                }
            } else {
                $faultHosts = array();
                $faultHosts[$host] = time();
            }

            shm_put_var($this->shmid, crc32('fault_hosts'), $faultHosts);
            sem_release($this->semid);
        }
    }
}