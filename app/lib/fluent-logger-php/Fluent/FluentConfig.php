<?php 

namespace Fluent;

class FluentConfig
{
  public static function createForwardTag($tag)
  {
    return 'forward.' . \Env::ENV . '_' . $tag;
  }
}