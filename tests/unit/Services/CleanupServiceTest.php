<?php
/**
 * Tests for CleanupService.
 *
 * @package VmfaFolderExporter\Tests
 */

declare(strict_types=1);

use VmfaFolderExporter\Services\CleanupService;
use Brain\Monkey\Functions;

beforeEach( function () {
	$this->service = new CleanupService();
} );

it( 'schedules cleanup if not already scheduled', function () {
	Functions\when( 'as_has_scheduled_action' )->justReturn( false );
	Functions\expect( 'as_schedule_recurring_action' )->once();

	$this->service->schedule_cleanup();
} );

it( 'does not re-schedule if already scheduled', function () {
	Functions\when( 'as_has_scheduled_action' )->justReturn( true );
	Functions\expect( 'as_schedule_recurring_action' )->never();

	$this->service->schedule_cleanup();
} );
