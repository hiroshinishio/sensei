<?php

class Sensei_Usage_Tracking_Data_Test extends WP_UnitTestCase {
	private $course_ids;
	private $modules;

	private function setupCoursesAndModules() {
		$this->course_ids = $this->factory->post->create_many( 3, array(
			'post_status' => 'publish',
			'post_type' => 'course',
		) );

		$this->modules = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->modules[] = wp_insert_term( 'Module ' . $i, 'module' );
		}

		// Add modules to courses.
		wp_set_object_terms( $this->course_ids[0],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
			),
			'module'
		);
		wp_set_object_terms( $this->course_ids[1],
			array(
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
		wp_set_object_terms( $this->course_ids[2],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
	}

	// Create some published and unpublished lessons.
	private function createLessons() {
		$drafts = $this->factory->post->create_many( 2, array(
			'post_status' => 'draft',
			'post_type' => 'lesson',
		) );
		$published = $this->factory->post->create_many( 3, array(
			'post_status' => 'publish',
			'post_type' => 'lesson',
		) );

		return array_merge( $drafts, $published );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataCourses() {
		$published = 4;

		// Create some published and unpublished courses.
		$this->factory->post->create_many( 2, array(
			'post_status' => 'draft',
			'post_type' => 'course',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'course',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'courses', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['courses'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_learner_count
	 */
	public function testGetUsageDataLearners() {
		// Create some users.
		$subscribers = $this->factory->user->create_many( 8, array( 'role' => 'subscriber' ) );
		$editors = $this->factory->user->create_many( 3, array( 'role' => 'editor' ) );

		// Enroll some users in multiple courses.
		foreach( $subscribers as $subscriber ) {
			$this->factory->comment->create( array(
				'user_id' => $subscriber,
				'comment_post_ID' => $this->course_ids[0],
				'comment_type' => 'sensei_course_status',
			) );

			$this->factory->comment->create( array(
				'user_id' => $subscriber,
				'comment_post_ID' => $this->course_ids[1],
				'comment_type' => 'sensei_course_status',
			) );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		// Despite being enrolled in multiple courses, a learner is only counted once.
		$this->assertArrayHasKey( 'learners', $usage_data, 'Key' );
		$this->assertEquals( count( $subscribers ), $usage_data['learners'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataLessons() {
		$this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lessons', $usage_data, 'Key' );
		$this->assertEquals( 3, $usage_data['lessons'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_prerequisite_count
	 */
	public function testGetLessonPrerequisiteCount() {
		$lessons = $this->createLessons();

		// Make some lessons prerequisites of others.
		add_post_meta( $lessons[1], '_lesson_prerequisite', $lessons[0] );	// Draft
		add_post_meta( $lessons[2], '_lesson_prerequisite', $lessons[1] );	// Published
		add_post_meta( $lessons[3], '_lesson_prerequisite', $lessons[2] );	// Published

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_prereqs', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['lesson_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_prerequisite_count
	 */
	public function testGetLessonPrerequisiteCountNoPrerequisites() {
		$lessons = $this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_prereqs', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_preview_count
	 */
	public function testGetLessonPreviewCount() {
		$lessons = $this->createLessons();

		// Turn on previews for some lessons.
		add_post_meta( $lessons[0], '_lesson_preview', 'preview' ); // Draft
		add_post_meta( $lessons[2], '_lesson_preview', 'preview' ); // Published
		add_post_meta( $lessons[3], '_lesson_preview', 'preview' ); // Published

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_previews', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['lesson_previews'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_preview_count
	 */
	public function testGetLessonPreviewCountNoPreviews() {
		$lessons = $this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_previews', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_previews'], 'Count' );
	}

		/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_module_count
	 */
	public function testGetLessonModuleCount() {
		$lessons = $this->createLessons();
		$terms = $this->factory->term->create_many( 3, array( 'taxonomy' => 'module' ) );

		// Assign modules to some lessons.
		wp_set_object_terms( $lessons[0], $terms[0], 'module', false ); // Draft
		wp_set_object_terms( $lessons[2], $terms[1], 'module', false ); // Published
		wp_set_object_terms( $lessons[4], $terms[2], 'module', false ); // Published

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_modules', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['lesson_modules'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_lesson_module_count
	 */
	public function testGetLessonModuleCountNoModules() {
		$lessons = $this->createLessons();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'lesson_modules', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['lesson_modules'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataMessages() {
		$published = 10;

		// Create some published and unpublished messages.
		$this->factory->post->create_many( 5, array(
			'post_status' => 'pending',
			'post_type' => 'sensei_message',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'sensei_message',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'messages', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['messages'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataModules() {
		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'modules', $usage_data, 'Key' );
		$this->assertEquals( count( $this->modules ), $usage_data['modules'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_max_module_count
	 */
	public function testGetUsageDataMaxModules() {
		$this->setupCoursesAndModules();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'modules_max', $usage_data, 'Key' );
		$this->assertEquals( 3, $usage_data['modules_max'], 'Count' ); // Course 2 has 3 modules.
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_min_module_count
	 */
	public function testGetUsageDataMinModules() {
		$this->setupCoursesAndModules();

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'modules_min', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['modules_min'], 'Count' ); // Courses 1 and 2 have 2 modules.
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 */
	public function testGetUsageDataQuestions() {
		$published = 15;

		// Create some published and unpublished questions.
		$this->factory->post->create_many( 12, array(
			'post_status' => 'private',
			'post_type' => 'question',
		) );
		$this->factory->post->create_many( $published, array(
			'post_status' => 'publish',
			'post_type' => 'question',
		) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'questions', $usage_data, 'Key' );
		$this->assertEquals( $published, $usage_data['questions'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_question_type_count
	 * @covers Sensei_Usage_Tracking_Data::get_question_type_key
	 */
	public function testGetUsageDataQuestionTypes() {
		// Create some questions.
		$questions = $this->factory->post->create_many( 10, array(
			'post_type' => 'question',
			'post_status' => 'publish',
		) );

		// Set the type of each question.
		wp_set_post_terms( $questions[0], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[1], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[2], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[3], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[4], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[5], array( 'gap-fill' ), 'question-type' );
		wp_set_post_terms( $questions[6], array( 'single-line' ), 'question-type' );
		wp_set_post_terms( $questions[7], array( 'boolean' ), 'question-type' );
		wp_set_post_terms( $questions[8], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[9], array( 'boolean' ), 'question-type' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'question_multiple_choice', $usage_data, 'Multiple choice key' );
		$this->assertArrayHasKey( 'question_gap_fill', $usage_data, 'Gap fill key' );
		$this->assertArrayHasKey( 'question_boolean', $usage_data, 'Boolean key' );
		$this->assertArrayHasKey( 'question_single_line', $usage_data, 'Single line key' );
		$this->assertArrayHasKey( 'question_multi_line', $usage_data, 'Multi line key' );
		$this->assertArrayHasKey( 'question_file_upload', $usage_data, 'File upload key' );

		$this->assertEquals( 3, $usage_data['question_multiple_choice'], 'Multiple choice count' );
		$this->assertEquals( 1, $usage_data['question_gap_fill'], 'Gap fill count' );
		$this->assertEquals( 2, $usage_data['question_boolean'], 'Boolean count' );
		$this->assertEquals( 1, $usage_data['question_single_line'], 'Single line count' );
		$this->assertEquals( 3, $usage_data['question_multi_line'], 'Multi line count' );
		$this->assertEquals( 0, $usage_data['question_file_upload'], 'File upload count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_question_type_count
	 */
	public function testGetUsageDataQuestionTypesInvalidType() {
		// Create a question.
		$questions = $this->factory->post->create( array(
			'post_type' => 'question',
			'post_status' => 'publish',
		) );

		// Set the question to use an invalid type.
		wp_set_post_terms( $questions[0], array( 'automattic' ), 'question-type' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayNotHasKey( 'question_automattic', $usage_data, 'Multiple choice key' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_random_order_count
	 */
	public function testGetRandomOrderCount() {
		// Create some questions.
		$questions[] = $this->factory->post->create_many( 3, array(
			'post_type' => 'question',
			'post_status' => 'publish',
		) );

		// Set the type of each question to be multiple choice.
		wp_set_post_terms( $questions[0][0], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[0][1], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[0][2], array( 'multiple-choice' ), 'question-type' );

		// Set the random answer order.
		add_post_meta( $questions[0][0], '_random_order', 'yes' );
		add_post_meta( $questions[0][1], '_random_order', 'no' );
		add_post_meta( $questions[0][2], '_random_order', 'yes' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'random_order', $usage_data, 'Key' );
		$this->assertEquals( 2, $usage_data['random_order'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_random_order_count
	 */
	public function testGetRandomOrderCountMultipleChoiceOnly() {
		// Create some questions.
		$questions[] = $this->factory->post->create_many( 6, array(
			'post_type' => 'question',
			'post_status' => 'publish',
		) );

		// Create a question of each type.
		wp_set_post_terms( $questions[0][0], array( 'multiple-choice' ), 'question-type' );
		wp_set_post_terms( $questions[0][1], array( 'multi-line' ), 'question-type' );
		wp_set_post_terms( $questions[0][2], array( 'single-line' ), 'question-type' );
		wp_set_post_terms( $questions[0][3], array( 'boolean' ), 'question-type' );
		wp_set_post_terms( $questions[0][4], array( 'file-upload' ), 'question-type' );
		wp_set_post_terms( $questions[0][5], array( 'gap-fill' ), 'question-type' );


		// Turn on random answer order for non-multiple choice questions.
		add_post_meta( $questions[0][0], '_random_order', 'no' );
		add_post_meta( $questions[0][1], '_random_order', 'yes' );
		add_post_meta( $questions[0][2], '_random_order', 'yes' );
		add_post_meta( $questions[0][3], '_random_order', 'yes' );
		add_post_meta( $questions[0][4], '_random_order', 'yes' );
		add_post_meta( $questions[0][5], '_random_order', 'yes' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'random_order', $usage_data, 'Key' );
		$this->assertEquals( 0, $usage_data['random_order'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_teacher_count
	 */
	public function testGetUsageDataTeachers() {
		$teachers = 3;

		// Create some users and teachers.
		$this->factory->user->create_many( 10, array( 'role' => 'subscriber' ) );
		$this->factory->user->create_many( $teachers, array( 'role' => 'teacher' ) );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'teachers', $usage_data, 'Key' );
		$this->assertEquals( $teachers, $usage_data['teachers'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_courses_with_video_count
	 */
	public function testGetCoursesWithVideoCount() {
		$with_video = 2;

		$course_ids_without_video = $this->factory->post->create_many( 3, array(
			'post_type' => 'course',
		) );
		$course_ids_with_video = $this->factory->post->create_many( $with_video, array(
			'post_type' => 'course',
		) );

		// Set video on courses
		foreach ( $course_ids_with_video as $course_id ) {
			update_post_meta( $course_id, '_course_video_embed', '<iframe src="video.com"></iframe' );
		}

		// Set some non-null values on the others
		update_post_meta( $course_ids_without_video[0], '_course_video_embed', '' );
		update_post_meta( $course_ids_without_video[1], '_course_video_embed', '   ' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_videos', $usage_data, 'Key' );
		$this->assertEquals( $with_video, $usage_data['course_videos'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_courses_with_disabled_notification_count
	 */
	public function testGetCoursesWithDisabledNotificationCount() {
		$with_disabled_notification = 2;

		$course_ids_without_disabled = $this->factory->post->create_many( 3, array(
			'post_type' => 'course',
		) );
		$course_ids_with_disabled = $this->factory->post->create_many( $with_disabled_notification, array(
			'post_type' => 'course',
		) );

		// Disable notifications
		foreach ( $course_ids_with_disabled as $course_id ) {
			update_post_meta( $course_id, 'disable_notification', true );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_no_notifications', $usage_data, 'Key' );
		$this->assertEquals( $with_disabled_notification, $usage_data['course_no_notifications'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_courses_with_prerequisite count
	 */
	public function testGetCoursesWithPrerequisiteCount() {
		$with_prereq = 2;

		$course_ids_without_prereq = $this->factory->post->create_many( 3, array(
			'post_type' => 'course',
		) );
		$course_ids_with_prereq = $this->factory->post->create_many( $with_prereq, array(
			'post_type' => 'course',
		) );

		// Set prerequisite on courses
		foreach ( $course_ids_with_prereq as $course_id ) {
			update_post_meta( $course_id, '_course_prerequisite', $course_ids_without_prereq[0] );
		}

		// Another value for no prereq
		update_post_meta( $course_ids_without_prereq[1], '_course_prerequisite', '0' );

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'course_prereqs', $usage_data, 'Key' );
		$this->assertEquals( $with_prereq, $usage_data['course_prereqs'], 'Count' );
	}

	/**
	 * @covers Sensei_Usage_Tracking_Data::get_usage_data
	 * @covers Sensei_Usage_Tracking_Data::get_featured_courses_count
	 */
	public function testGetFeaturedCoursesCount() {
		$featured = 2;

		$non_featured_course_ids = $this->factory->post->create_many( 3, array(
			'post_type' => 'course',
		) );
		$featured_course_ids = $this->factory->post->create_many( $featured, array(
			'post_type' => 'course',
		) );

		// Set courses to featured
		foreach ( $featured_course_ids as $course_id ) {
			update_post_meta( $course_id, '_course_featured', 'featured' );
		}

		$usage_data = Sensei_Usage_Tracking_Data::get_usage_data();

		$this->assertArrayHasKey( 'featured_courses', $usage_data, 'Key' );
		$this->assertEquals( $featured, $usage_data['featured_courses'], 'Count' );
	}
}
