<?php

namespace App\Payments\Exceptions;

use InvalidArgumentException;

// Only thrown before the provider create request is dispatched.
final class CheckoutNotDispatched extends InvalidArgumentException {}
