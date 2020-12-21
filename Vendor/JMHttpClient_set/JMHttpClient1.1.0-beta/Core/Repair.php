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
    static protected $instances = array();
    protected $semid = null;
    protected $shmid = null;
    protected $shmsize = 10240;

    /**
     * Get a instance.
     *
     * @param string $tag  Config tag.
     * @param int    $size Shard memory size.
     *
     * @return \JMHttpClient\Core\Repair
     */
    static public function instance($tag, $size = 0)
    {
        if (! isset(self::$instances[$tag])) {
            self::$instances[$tag] = new self($tag, $size);
        }

        return self::$instances[$tag];
    }

    /**
     * Constructor.
     *
     * @param string $tag  Config tag.
     * @param int    $size Shard memory size.
     */
    protected function __construct($tag, $size = 0)
    {
        if ($size > 0) {
            $this->shmsize = $size;
        }

        $this->semid = sem_get(ftok(__FILE__, chr(crc32("$tag:SEM") % 256)));
        $this->shmid = shm_attach(ftok(__FILE__, chr(crc32("$tag:SHM") % 256)), $this->shmsize);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        shm_detach($this->shmid);
    }

    /**
     * Get fault host.
     *
     * @return array
     */
    public function getFaultHosts()
    {
        $faultHosts = array();
        if (sem_acquire($this->semid)) {
            if (shm_has_var($this->shmid, crc32('fault_hosts'))) {
                $faultHosts = shm_get_var($this->shmid, crc32('fault_hosts'));
            }

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
        if (sem_acquire($this->semid)) {
            if (shm_has_var($this->shmid, crc32('fault_hosts'))) {
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