<?php

namespace DevCoding\Mac\Utility;

use DevCoding\Command\Base\Traits\ShellTrait;

trait MacShellTrait
{
  use ShellTrait;

  /**
   * Returns the username of the user currently logged into the macOS GUI.
   *
   * @return string|null
   */
  protected function getConsoleUser()
  {
    return $this->getShellExec("/usr/sbin/scutil <<< \"show State:/Users/ConsoleUser\" | /usr/bin/awk '/Name :/ && ! /loginwindow/ { print $3 }'");
  }

  /**
   * Returns the user id of the given username.
   *
   * @param string $user
   *
   * @return string|null
   */
  protected function getUserId($user)
  {
    return $this->getShellExec(sprintf('/usr/bin/id -u %s', $user));
  }
}
