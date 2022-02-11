<?php
namespace Oka\CORSBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class OkaCORSBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
