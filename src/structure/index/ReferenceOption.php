<?php
declare(strict_types = 1);

namespace mheinzerling\commons\database\structure\index;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static ReferenceOption RESTRICT()
 * @method static ReferenceOption CASCADE()
 * @method static ReferenceOption SET_NULL()
 * @method static ReferenceOption NO_ACTION()
 */
final class ReferenceOption extends AbstractEnumeration
{
    const RESTRICT = 'RESTRICT';
    const CASCADE = 'CASCADE';
    const SET_NULL = 'SET NULL';
    const NO_ACTION = 'NO ACTION';
}
