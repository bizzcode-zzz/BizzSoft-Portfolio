<?php

namespace App\Payments\Exceptions;

use RuntimeException;

// The provider received the create request and definitively rejected it.
final class CheckoutProviderRejected extends RuntimeException {}