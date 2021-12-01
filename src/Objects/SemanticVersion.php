<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Object\System\Version\Version;

class SemanticVersion extends Version
{
  public function getRaw()
  {
    return $this->raw;
  }

  public function getRevision()
  {
    return $this->getPatch();
  }
}
