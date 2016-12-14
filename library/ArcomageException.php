<?php

class ArcomageException extends \Exception
{
    const ERROR     = 0;
    const WARNING   = 1;
    const NOTICE    = 2;
    const INFO      = 3;
    const DEBUG     = 4;
    const CRITICAL  = 5;
    const ALERT     = 6;
    const EMERGENCY = 7;
}
