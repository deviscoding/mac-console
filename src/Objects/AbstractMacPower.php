<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Command\Base\Traits\ShellTrait;

abstract class AbstractMacPower
{
  use ShellTrait;

  protected function getPowerDataType($key)
  {
    $data = explode("\n", $this->getShellExec('system_profiler SPPowerDataType'));

    foreach ($data as $line)
    {
      if (!empty($line))
      {
        if (false !== stripos($line, $key.':'))
        {
          return trim(explode(':', $line)[1]);
        }
      }
    }

    return null;
  }

  protected function getPmsetPs()
  {
    return $this->getShellExec('/usr/bin/pmset -g ps');
  }

  protected function getPmsetBatt()
  {
    return str_replace("\n", ' ', $this->getShellExec('/usr/bin/pmset -g batt'));
  }
}
