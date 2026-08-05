<?php

namespace App\Services;

use App\Core\ErrorDefinition\ContextSanitizer;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function __construct(
        private readonly ?ContextSanitizer $sanitizer = null,
    ) {}

    /**
     * Menyimpan satu record activity log.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $subjectType,
        string $subjectId,
        string $event,
        array $properties = [],
        ?string $causerId = null,
        ?string $impersonatorId = null
    ): ActivityLog {
        $finalCauserId = $causerId;
        if ($finalCauserId === null) {
            $user = Auth::user();
            $finalCauserId = $user instanceof User ? $user->v_userid : 'SYSTEM';
        }

        $finalImpersonatorId = $impersonatorId;
        if ($finalImpersonatorId === null) {
            if (session()->has('impersonator_id')) {
                $sessionImpersonator = session()->get('impersonator_id');
                if (is_string($sessionImpersonator) && $sessionImpersonator !== '') {
                    $finalImpersonatorId = $sessionImpersonator;
                }
            }
        }

        $requestId = null;
        $reqAttrId = request()->attributes->get('request_id');
        if (is_string($reqAttrId) && $reqAttrId !== '') {
            $requestId = $reqAttrId;
        } else {
            $headerId = request()->header('X-Request-Id');
            if (is_string($headerId) && $headerId !== '') {
                $requestId = $headerId;
            }
        }

        $ipAddress = request()->ip();

        $sanitizerInstance = $this->sanitizer ?? ContextSanitizer::fromConfig();
        $sanitizedProperties = $sanitizerInstance->sanitize($properties);

        return ActivityLog::create([
            'v_subject_type' => $subjectType,
            'v_subject_id' => $subjectId,
            'v_event' => $event,
            'v_causer_id' => $finalCauserId,
            'v_impersonator_id' => $finalImpersonatorId,
            'v_request_id' => $requestId,
            'v_ip_address' => $ipAddress,
            'j_properties' => $sanitizedProperties,
            'dt_created_at' => Carbon::now(),
        ]);
    }
}
