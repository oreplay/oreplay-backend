<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class Initial extends AbstractMigration
{
    public function up(): void
    {
        $this->table('answers', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('runner_result_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('order_number', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('given', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('correct', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('time_seconds', 'decimal', [
                'default' => '0.00',
                'null' => true,
                'precision' => 8,
                'scale' => 2,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'answers_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'answers_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'runner_result_id',
                ],
                [
                    'name' => 'answers_ibfk_3',
                ]
            )
            ->create();

        $this->table('classes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('course_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('oe_key', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('short_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('long_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'classes_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'classes_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'course_id',
                ],
                [
                    'name' => 'classes_ibfk_3',
                ]
            )
            ->create();

        $this->table('classes_controls', ['id' => false, 'primary_key' => ['class_id', 'control_id', 'id_leg', 'id_revisit']])
            ->addColumn('class_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('control_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('id_leg', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('id_revisit', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('order_number', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kilometer', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 6,
                'scale' => 2,
            ])
            ->addColumn('relative_position', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('controls', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'classes_controls_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'classes_controls_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                ],
                [
                    'name' => 'classes_controls_ibfk_3',
                ]
            )
            ->addIndex(
                [
                    'control_id',
                ],
                [
                    'name' => 'classes_controls_ibfk_4',
                ]
            )
            ->create();

        $this->table('clubs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('oe_key', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('short_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('long_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('city', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('logo', 'binary', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'clubs_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'clubs_ibfk_2',
                ]
            )
            ->create();

        $this->table('control_types')
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('controls', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('control_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('station', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('coord_system', 'char', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('datum', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('utm_zone', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('hemisphere', 'char', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('latitude', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 14,
                'scale' => 6,
            ])
            ->addColumn('longitude', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 14,
                'scale' => 6,
            ])
            ->addColumn('control_type_id', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('battery_perc', 'integer', [
                'default' => '100',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('last_reading', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'controls_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'controls_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'control_type_id',
                ],
                [
                    'name' => 'controls_ibfk_3',
                ]
            )
            ->create();

        $this->table('courses', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('oe_key', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('short_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('long_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('distance', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('climb', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('controls', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('coord_system', 'char', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('datum', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('utm_zone', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('hemisphere', 'char', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('latitude', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 14,
                'scale' => 6,
            ])
            ->addColumn('longitude', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 14,
                'scale' => 6,
            ])
            ->addColumn('zoom', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'courses_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'courses_ibfk_2',
                ]
            )
            ->create();

        $this->table('events')
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('initial_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('final_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('federation_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'federation_id',
                ],
                [
                    'name' => 'events_ibfk_1',
                ]
            )
            ->create();

        $this->table('federations', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('result_types')
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('runner_results', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('runner_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('class_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('stage_order', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('runner_uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('class_uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('result_type_id', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('check_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('finish_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_seconds', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('position', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('status_code', 'char', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('time_behind', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_neutralization', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_adjusted', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_penalty', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_bonus', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_final', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_adjusted', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_penalty', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_bonus', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('leg_number', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'runner_results_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'runner_results_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'runner_id',
                ],
                [
                    'name' => 'runner_results_ibfk_3',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                ],
                [
                    'name' => 'runner_results_ibfk_4',
                ]
            )
            ->addIndex(
                [
                    'result_type_id',
                ],
                [
                    'name' => 'runner_results_ibfk_5',
                ]
            )
            ->create();

        $this->table('runners', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('first_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('last_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('db_id', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('iof_id', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('bib_number', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('bib_alt', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('sicard', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('sicard_alt', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('license', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('national_id', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('birth_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('sex', 'char', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('telephone1', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('telephone2', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('user_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('class_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('class_uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('club_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('team_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('leg_number', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'runners_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'runners_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                ],
                [
                    'name' => 'runners_ibfk_3',
                ]
            )
            ->addIndex(
                [
                    'club_id',
                ],
                [
                    'name' => 'runners_ibfk_4',
                ]
            )
            ->addIndex(
                [
                    'team_id',
                ],
                [
                    'name' => 'runners_ibfk_5',
                ]
            )
            ->addIndex(
                [
                    'user_id',
                ],
                [
                    'name' => 'runners_ibfk_6',
                ]
            )
            ->create();

        $this->table('splits', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_order', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('sicard', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('station', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('reading_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('reading_milli', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('runner_result_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('team_result_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('class_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('control_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('id_leg', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('id_revisit', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('runner_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('team_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('bib_runner', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('bib_team', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('club_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('order_number', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('battery_perc', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('battery_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('raw_value', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'splits_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'club_id',
                ],
                [
                    'name' => 'splits_ibfk_10',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'splits_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'runner_result_id',
                ],
                [
                    'name' => 'splits_ibfk_3',
                ]
            )
            ->addIndex(
                [
                    'team_result_id',
                ],
                [
                    'name' => 'splits_ibfk_4',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                ],
                [
                    'name' => 'splits_ibfk_5',
                ]
            )
            ->addIndex(
                [
                    'control_id',
                ],
                [
                    'name' => 'splits_ibfk_6',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                    'control_id',
                    'id_leg',
                    'id_revisit',
                ],
                [
                    'name' => 'splits_ibfk_7',
                ]
            )
            ->addIndex(
                [
                    'runner_id',
                ],
                [
                    'name' => 'splits_ibfk_8',
                ]
            )
            ->addIndex(
                [
                    'team_id',
                ],
                [
                    'name' => 'splits_ibfk_9',
                ]
            )
            ->addIndex(
                [
                    'sicard',
                    'station',
                    'reading_milli',
                ],
                [
                    'name' => 'splits_lectura',
                ]
            )
            ->addIndex(
                [
                    'event_id',
                    'stage_id',
                    'sicard',
                    'station',
                    'reading_milli',
                ],
                [
                    'name' => 'splits_lectura2',
                ]
            )
            ->create();

        $this->table('stage_types')
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('stages')
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('base_date', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('base_time', 'time', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('order_number', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('stage_type_id', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('server_offset', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('utc_value', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'stages_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_type_id',
                ],
                [
                    'name' => 'stages_ibfk_2',
                ]
            )
            ->create();

        $this->table('team_results', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('team_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('class_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('stage_order', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('team_uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('class_uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('result_type_id', 'integer', [
                'default' => '1',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('check_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('start_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('finish_time', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_seconds', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('position', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('status_code', 'char', [
                'default' => '0',
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('time_behind', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_neutralization', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_adjusted', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_penalty', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('time_bonus', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_final', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_adjusted', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_penalty', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('points_bonus', 'integer', [
                'default' => '0',
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'team_results_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'team_results_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'team_id',
                ],
                [
                    'name' => 'team_results_ibfk_3',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                ],
                [
                    'name' => 'team_results_ibfk_4',
                ]
            )
            ->addIndex(
                [
                    'result_type_id',
                ],
                [
                    'name' => 'team_results_ibfk_5',
                ]
            )
            ->create();

        $this->table('teams', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('stage_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('bib_number', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('bib_alt', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('team_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('sicard', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('sicard_alt', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('class_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('class_uuid', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('club_id', 'biginteger', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('legs', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'teams_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'stage_id',
                ],
                [
                    'name' => 'teams_ibfk_2',
                ]
            )
            ->addIndex(
                [
                    'class_id',
                ],
                [
                    'name' => 'teams_ibfk_3',
                ]
            )
            ->addIndex(
                [
                    'club_id',
                ],
                [
                    'name' => 'teams_ibfk_4',
                ]
            )
            ->create();

        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('first_name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('last_name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('is_admin', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('is_super', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('users_events', ['id' => false, 'primary_key' => ['user_id', 'event_id']])
            ->addColumn('user_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('event_id', 'integer', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('is_admin', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'user_id',
                ],
                [
                    'name' => 'users_events_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'event_id',
                ],
                [
                    'name' => 'users_events_ibfk_2',
                ]
            )
            ->create();

        $this->table('users_federations', ['id' => false, 'primary_key' => ['user_id', 'federation_id']])
            ->addColumn('user_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('federation_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('uuid_value', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'user_id',
                ],
                [
                    'name' => 'users_federations_ibfk_1',
                ]
            )
            ->addIndex(
                [
                    'federation_id',
                ],
                [
                    'name' => 'users_federations_ibfk_2',
                ]
            )
            ->create();

        $this->table('answers')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'answers_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'answers_ibfk_2'
                ]
            )
            ->addForeignKey(
                'runner_result_id',
                'runner_results',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'answers_ibfk_3'
                ]
            )
            ->update();

        $this->table('classes')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'classes_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'classes_ibfk_2'
                ]
            )
            ->addForeignKey(
                'course_id',
                'courses',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'classes_ibfk_3'
                ]
            )
            ->update();

        $this->table('classes_controls')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'classes_controls_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'classes_controls_ibfk_2'
                ]
            )
            ->addForeignKey(
                'class_id',
                'classes',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'classes_controls_ibfk_3'
                ]
            )
            ->addForeignKey(
                'control_id',
                'controls',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'classes_controls_ibfk_4'
                ]
            )
            ->update();

        $this->table('clubs')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'clubs_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'clubs_ibfk_2'
                ]
            )
            ->update();

        $this->table('controls')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'controls_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'controls_ibfk_2'
                ]
            )
            ->addForeignKey(
                'control_type_id',
                'control_types',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'controls_ibfk_3'
                ]
            )
            ->update();

        $this->table('courses')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'courses_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'courses_ibfk_2'
                ]
            )
            ->update();

        $this->table('events')
            ->addForeignKey(
                'federation_id',
                'federations',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'events_ibfk_1'
                ]
            )
            ->update();

        $this->table('runner_results')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'runner_results_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'runner_results_ibfk_2'
                ]
            )
            ->addForeignKey(
                'runner_id',
                'runners',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'runner_results_ibfk_3'
                ]
            )
            ->addForeignKey(
                'class_id',
                'classes',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'runner_results_ibfk_4'
                ]
            )
            ->addForeignKey(
                'result_type_id',
                'result_types',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'runner_results_ibfk_5'
                ]
            )
            ->update();

        $this->table('runners')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'runners_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'runners_ibfk_2'
                ]
            )
            ->addForeignKey(
                'class_id',
                'classes',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'runners_ibfk_3'
                ]
            )
            ->addForeignKey(
                'club_id',
                'clubs',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'runners_ibfk_4'
                ]
            )
            ->addForeignKey(
                'team_id',
                'teams',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'runners_ibfk_5'
                ]
            )
            ->addForeignKey(
                'user_id',
                'users',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'runners_ibfk_6'
                ]
            )
            ->update();

        $this->table('splits')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'splits_ibfk_1'
                ]
            )
            ->addForeignKey(
                'club_id',
                'clubs',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_10'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'splits_ibfk_2'
                ]
            )
            ->addForeignKey(
                'runner_result_id',
                'runner_results',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_3'
                ]
            )
            ->addForeignKey(
                'team_result_id',
                'team_results',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_4'
                ]
            )
            ->addForeignKey(
                'class_id',
                'classes',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_5'
                ]
            )
            ->addForeignKey(
                'control_id',
                'controls',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'NO_ACTION',
                    'constraint' => 'splits_ibfk_6'
                ]
            )
            ->addForeignKey(
                [
                    'class_id',
                    'control_id',
                    'id_leg',
                    'id_revisit',
                ],
                'classes_controls',
                [
                    'class_id',
                    'control_id',
                    'id_leg',
                    'id_revisit',
                ],
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_7'
                ]
            )
            ->addForeignKey(
                'runner_id',
                'runners',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_8'
                ]
            )
            ->addForeignKey(
                'team_id',
                'teams',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'splits_ibfk_9'
                ]
            )
            ->update();

        $this->table('stages')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'stages_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_type_id',
                'stage_types',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'stages_ibfk_2'
                ]
            )
            ->update();

        $this->table('team_results')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'team_results_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'team_results_ibfk_2'
                ]
            )
            ->addForeignKey(
                'team_id',
                'teams',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'team_results_ibfk_3'
                ]
            )
            ->addForeignKey(
                'class_id',
                'classes',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'team_results_ibfk_4'
                ]
            )
            ->addForeignKey(
                'result_type_id',
                'result_types',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'team_results_ibfk_5'
                ]
            )
            ->update();

        $this->table('teams')
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'teams_ibfk_1'
                ]
            )
            ->addForeignKey(
                'stage_id',
                'stages',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'teams_ibfk_2'
                ]
            )
            ->addForeignKey(
                'class_id',
                'classes',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'teams_ibfk_3'
                ]
            )
            ->addForeignKey(
                'club_id',
                'clubs',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'SET_NULL',
                    'constraint' => 'teams_ibfk_4'
                ]
            )
            ->update();

        $this->table('users_events')
            ->addForeignKey(
                'user_id',
                'users',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'users_events_ibfk_1'
                ]
            )
            ->addForeignKey(
                'event_id',
                'events',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'users_events_ibfk_2'
                ]
            )
            ->update();

        $this->table('users_federations')
            ->addForeignKey(
                'user_id',
                'users',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'users_federations_ibfk_1'
                ]
            )
            ->addForeignKey(
                'federation_id',
                'federations',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                    'constraint' => 'users_federations_ibfk_2'
                ]
            )
            ->update();
    }

    public function down(): void
    {
        $this->table('answers')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'runner_result_id'
            )->save();

        $this->table('classes')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'course_id'
            )->save();

        $this->table('classes_controls')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'class_id'
            )
            ->dropForeignKey(
                'control_id'
            )->save();

        $this->table('clubs')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )->save();

        $this->table('controls')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'control_type_id'
            )->save();

        $this->table('courses')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )->save();

        $this->table('events')
            ->dropForeignKey(
                'federation_id'
            )->save();

        $this->table('runner_results')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'runner_id'
            )
            ->dropForeignKey(
                'class_id'
            )
            ->dropForeignKey(
                'result_type_id'
            )->save();

        $this->table('runners')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'class_id'
            )
            ->dropForeignKey(
                'club_id'
            )
            ->dropForeignKey(
                'team_id'
            )
            ->dropForeignKey(
                'user_id'
            )->save();

        $this->table('splits')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'club_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'runner_result_id'
            )
            ->dropForeignKey(
                'team_result_id'
            )
            ->dropForeignKey(
                'class_id'
            )
            ->dropForeignKey(
                'control_id'
            )
            ->dropForeignKey(
                [
                    'class_id',
                    'control_id',
                    'id_leg',
                    'id_revisit',
                ]
            )
            ->dropForeignKey(
                'runner_id'
            )
            ->dropForeignKey(
                'team_id'
            )->save();

        $this->table('stages')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_type_id'
            )->save();

        $this->table('team_results')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'team_id'
            )
            ->dropForeignKey(
                'class_id'
            )
            ->dropForeignKey(
                'result_type_id'
            )->save();

        $this->table('teams')
            ->dropForeignKey(
                'event_id'
            )
            ->dropForeignKey(
                'stage_id'
            )
            ->dropForeignKey(
                'class_id'
            )
            ->dropForeignKey(
                'club_id'
            )->save();

        $this->table('users_events')
            ->dropForeignKey(
                'user_id'
            )
            ->dropForeignKey(
                'event_id'
            )->save();

        $this->table('users_federations')
            ->dropForeignKey(
                'user_id'
            )
            ->dropForeignKey(
                'federation_id'
            )->save();

        $this->table('answers')->drop()->save();
        $this->table('classes')->drop()->save();
        $this->table('classes_controls')->drop()->save();
        $this->table('clubs')->drop()->save();
        $this->table('control_types')->drop()->save();
        $this->table('controls')->drop()->save();
        $this->table('courses')->drop()->save();
        $this->table('events')->drop()->save();
        $this->table('federations')->drop()->save();
        $this->table('result_types')->drop()->save();
        $this->table('runner_results')->drop()->save();
        $this->table('runners')->drop()->save();
        $this->table('splits')->drop()->save();
        $this->table('stage_types')->drop()->save();
        $this->table('stages')->drop()->save();
        $this->table('team_results')->drop()->save();
        $this->table('teams')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('users_events')->drop()->save();
        $this->table('users_federations')->drop()->save();
    }
}
