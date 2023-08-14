<?php
/**
 * File containing the Action_Scheduler class.
 *
 * @package sensei-lms
 *
 * @internal
 */

namespace Sensei\Internal\Action_Scheduler;

require_once realpath( __DIR__ . '/../../lib/action-scheduler/action-scheduler.php' );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Action_Scheduler
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Action_Scheduler {
	/**
	 * Action Scheduler group ID.
	 *
	 * @var string
	 */
	public const GROUP_ID = 'sensei-lms-jobs';

	/**
	 * Schedule a recurring action.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int    $timestamp           Timestamp for when to run the action.
	 * @param int    $interval_in_seconds Interval in seconds between each run of the action.
	 * @param string $hook                Action hook to execute.
	 * @param array  $args                Arguments to pass to the hook's callback function.
	 * @param bool   $unique              Whether to schedule the action only if it is not already scheduled.
	 * @return int The scheduled action ID.
	 */
	public function schedule_recurring_action(int $timestamp, int $interval_in_seconds, string $hook, array $args = [], bool $unique = true): int {
		return as_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args, self::GROUP_ID, $unique);
	}

	/**
	 * Schedule a single action immediately.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param string $hook   Action hook to execute.
	 * @param array  $args   Arguments to pass to the hook's callback function.
	 * @param bool   $unique Whether to schedule the action only if it is not already scheduled.
	 * @return int The scheduled action ID.
	 */
	public function schedule_immediate_single_action(string $hook, array $args = [], bool $unique = true): int {
		return as_schedule_single_action(time(), $hook, $args, self::GROUP_ID, $unique);
	}

	/**
	 * Unschedule a single action.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param string $hook Action hook to execute.
	 * @param array  $args Arguments to pass to the hook's callback function.
	 * @return int|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
	 */
	public function unschedule_action(string $hook, array $args = []): ?int {
		$id = as_unschedule_action($hook, $args, self::GROUP_ID);
		return $id !== null ? intval($id) : null;
	}

	/**
	 * Unschedule all actions.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function unschedule_all_cron_actions(): void {
		// Passing only group to unschedule all by group
		as_unschedule_all_actions('', [], self::GROUP_ID);
	}

	/**
	 * Check if there is a scheduled action in the queue but more efficiently than as_next_scheduled_action().
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param string $hook  The hook of the action.
	 * @param array  $args  Args that have been passed to the action. Null will matches any args.
	 * @return bool True if a matching action is pending or in-progress, false otherwise.
	 */
	public function has_scheduled_action(string $hook, array $args = []): bool {
		return as_has_scheduled_action($hook, $args, self::GROUP_ID);
	}
}

