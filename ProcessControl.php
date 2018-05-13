<?php

/**
 * Trait ProcessKill - Helper
 * Killed process by process name OR combine process name (control pid) and pid
 *
 * Used example: $this->killByPid(self::PROCESS_NAME_PREFIX,$pid);
 */
trait ProcessControl
{

    public function getProcess($processName)
    {
        exec('ps -aux | grep ' . $processName, $outs, $ret);
        $list = [];
        foreach ($outs as $i => $process) {
            $process = preg_replace('/\ {1,}/', '|', $process);
            $data = explode("|", $process);

            if (stripos($data[10], $processName) !== false) {
                $list[$data[10]][$data[1]] = $data[1];
            }
        }
        return $list;
    }

    public function hasPid($pid, $processName)
    {
        $hasPid = false;
        $processes = $this->getProcessInfo($processName);
        foreach ($processes as $processInfo) {
            foreach ($processInfo as $process) {
                if (isset($process['pid']) && $process['pid'] == $pid) {
                    $hasPid = true;
                    break;
                }
            }
        }
        return $hasPid;
    }

    public function getProcessInfo($processName)
    {
        exec('ps -aux | grep ' . $processName, $outs, $ret);
        $list = [];
        $outs = array_slice($outs, 0, count($outs) - 3);

        foreach ($outs as $i => $process) {
            $process = preg_replace('/\ {1,}/', '|', $process);
            $data = explode("|", $process);
            $list[$data[10]][$data[1]] = [
                'pid' => $data[1],
                'user' => $data[0],
                'status' => $data[7],
                'name' => $data[10],
                'startTime' => $data[8],
                'timeProcess' => $data[9],
            ];
        }

        return $list;
    }

    /**
     * @param $processName
     * @param int $time - 3 MINUTES
     *
     * @return array
     */
    public function getProcessInfoTime($processName, $time = 180, $status = 'S')
    {
        exec('ps -aux | grep ' . $processName, $outs, $ret);
        $list = [];
        $outs = array_slice($outs, 0, count($outs) - 3);

        $y = date('Y', time());
        $m = date('m', time());
        $d = date('d', time());

        $currentTime = time();

        foreach ($outs as $i => $process) {
            $process = preg_replace('/\ {1,}/', '|', $process);
            $data = explode("|", $process);
            if (in_array($data[10], ['sh', 'php', 'php-fpm', 'nginx'])) {
                continue;
            }
            $timeData = explode(':', $data[8]);
            $startTime = mktime($timeData[0], $timeData[1], 0, $m, $d, $y);
            $this->stdout(print_r($process, true) . PHP_EOL, \yii\helpers\Console::FG_YELLOW, \yii\helpers\Console::UNDERLINE);
            if ($data[7] == 'Z') {
                posix_kill($data[1], SIGKILL);
            } else {
                if ((($currentTime - $startTime) >= $time)) {
                    $list[$data[10]][] = [
                        'pid' => $data[1],
                        'user' => $data[0],
                        'name' => $data[10],
                        'status' => $data[7],
                        'startTime' => $data[8],
                        'timeProcess' => $data[9],
                    ];
                }
            }
        }
        return $list;
    }

    public function killZombyProcess($processName)
    {
        exec('ps -aux | grep ' . $processName, $outs, $ret);
        foreach ($outs as $i => $process) {
            $process = preg_replace('/\ {1,}/', '|', $process);
            $data = explode("|", $process);
            if ($data[7] == 'Z') {
                posix_kill($data[1], SIGKILL);
            }
        }
    }

    public function getCountProcess($processName)
    {
        return count($this->getProcess($processName));
    }

    /**
     * @param $processName
     */
    public function killedProcess($processName, $sig = SIGKILL)
    {
        echo PHP_EOL . ' РЈР±РёРІР°РµРј РїСЂРѕС†РµСЃСЃ ' . $processName . PHP_EOL;
        $processes = $this->getProcess($processName);
        foreach ($processes as $name => $pids) {
            if (is_array($pids)) {
                foreach ($pids as $pid) {
                    posix_kill($pid, $sig);
                }
            } else {
                posix_kill($pids, $sig);
            }
        }
    }

    /**
     * @param $processName
     * @param $pid
     */
    public function killByPid($processName, $pid, $sig = SIGKILL)
    {
        echo PHP_EOL . ' РЈР±РёРІР°РµРј РїСЂРѕС†РµСЃСЃ ' . $processName . '*' . ':' . $pid . PHP_EOL;
        $processes = $this->getProcess($processName);
        dump($processes, '$processes', 0);
        if (!is_array($processes)) {
            echo 'РџСЂРѕС†РµСЃСЃС‹ РїРѕ РјР°СЃРєРµ ' . $processName . ' РЅРµ РЅР°Р№РґРµРЅС‹...' . PHP_EOL;
            return;
        }
        if (isset($processes[$processName][$pid])) {
            posix_kill($pid, $sig);
        }
    }
}
