<?php

namespace App\Exceptions;

/**
 * RF-04: NIK yang di-submit lewat lengkapiKtp() sudah dipakai akun lain.
 * Dilempar dari ExtrasProfile::lengkapiKtp(), ditangkap di
 * Extras\ProfileController::simpanKtp() untuk pesan friendly.
 */
class NikDuplikatException extends \LogicException {}
