<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar Service kalau aturan bisnis dilanggar (stok kurang, status tidak sah, dll).
 * Pesannya ramah pengguna dan langsung ditampilkan sebagai toast di UI.
 */
class BusinessRuleException extends RuntimeException
{
}
