<?php


namespace Illuminate\Foundation\Http {

    use App\Models\User;

    class FormRequest
    {
        public function user($guard = null): ?User
        {
            return value(1);
        }
    }
}

namespace Illuminate\Contracts\Auth {

    use App\Models\User;

    interface Guard
    {
        public function user(): ?User;
    }
}
