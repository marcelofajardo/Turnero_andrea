<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\SettingsRepository;
use DateTimeImmutable;
use DateTimeZone;
use DateInterval;

/**
 * AppointmentAvailabilityService
 *
 * Core business logic for determining which time slots are available on a
 * given date for a given service. Considers:
 *  - Business hours (multiple ranges per day)
 *  - Duration per service
 *  - Already booked appointments (status != cancelled)
 *  - Timezone configuration
 */
final class AppointmentAvailabilityService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepo,
        private readonly SettingsRepository    $settingsRepo,
    ) {}

    /**
     * Return an array of available ISO-8601 time strings for the given date and service.
     *
     * @return array<string> e.g. ['09:00', '09:30', '10:00', ...]
     */
    public function getAvailableSlots(int $serviceId, string $date, int $durationMinutes): array
    {
        $timezone   = new DateTimeZone($this->settingsRepo->get('timezone', 'UTC'));
        $target     = DateTimeImmutable::createFromFormat('Y-m-d', $date, $timezone);

        if ($target === false) {
            return [];
        }

        // ISO day of week: 1=Mon, 7=Sun
        $dayOfWeek = (int) $target->format('N');

        // Get applicable business hours for this day
        $businessHours = $this->settingsRepo->getBusinessHours($serviceId);
        $dayRanges = array_filter(
            $businessHours,
            fn($h) => (int) $h['day_of_week'] === $dayOfWeek && (bool) $h['is_active']
        );

        if (empty($dayRanges)) {
            return [];  // Business closed on this day
        }

        // Get all existing bookings for this day (non-cancelled)
        $existingBookings = $this->appointmentRepo->findByDateAndService($serviceId, $date);
        $bookedStarts = array_map(
            fn($a) => $a->getAppointmentDatetime()->format('H:i'),
            $existingBookings
        );

        $slots = [];
        $interval = new DateInterval("PT{$durationMinutes}M");

        foreach ($dayRanges as $range) {
            $rangeStart = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                "{$date} {$range['start_time']}",
                $timezone
            );
            $rangeEnd = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                "{$date} {$range['end_time']}",
                $timezone
            );

            if ($rangeStart === false || $rangeEnd === false) continue;

            $cursor = $rangeStart;

            while (true) {
                $slotEnd = $cursor->add($interval);

                // Slot must end before or at range end
                if ($slotEnd > $rangeEnd) break;

                // Skip past slots (same-day bookings in the past)
                $now = new DateTimeImmutable('now', $timezone);
                if ($cursor <= $now) {
                    $cursor = $slotEnd;
                    continue;
                }

                $slotStr = $cursor->format('H:i');

                if (!in_array($slotStr, $bookedStarts, true)) {
                    $slots[] = $slotStr;
                }

                $cursor = $slotEnd;
            }
        }

        return $slots;
    }

    /**
     * Check if a specific slot is still available.
     * Used as a final validation before creating the appointment record.
     */
    public function isSlotAvailable(int $serviceId, string $date, string $time): bool
    {
        $existingBookings = $this->appointmentRepo->findByDateAndService($serviceId, $date);

        foreach ($existingBookings as $appointment) {
            if ($appointment->getAppointmentDatetime()->format('H:i') === $time) {
                return false;
            }
        }

        return true;
    }
}