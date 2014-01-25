<?php

class CP_Content_Reports_Subjects_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('subjects_manage', 'reports') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/reports', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/reports', __('reports', 'system_navigation'));
		view::setTrail('cp/content/reports/subjects', __('reports_subjects', 'system_navigation'));

		loader::model('reports/subjects', array(), 'reports_subjects_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get subjects
		if ( !( $subjects = $this->reports_subjects_model->getSubjects() ) )
		{
			view::setInfo(__('no_subjects', 'reports_subjects'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/reports/subjects/browse',
			'keyword' => 'reports_subjects',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'status' => array(
					'html' => __('status', 'system'),
					'class' => 'status',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $subjects as $subject )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/content/reports/subjects/edit/' . $subject['subject_id'], text_helper::truncate($subject['name'], 64)),
				),
				'status' => array(
					'html' => $subject['active'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/content/reports/subjects/edit/' . $subject['subject_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/reports/subjects/delete/' . $subject['subject_id'], __('delete', 'system'), array('data-html' => __('subject_delete?', 'reports_subjects'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/content/reports/subjects/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('reports_subjects_manage', 'system_navigation'));

		// Set action
		view::setAction('cp/content/reports/subjects/edit', __('subject_new', 'reports_subjects'), array('class' => 'icon-text icon-reports-subjects-new'));

		// Load view
		view::load('cp/content/reports/subjects/browse');
	}

	public function edit()
	{
		// Get URI vars
		$subjectID = (int)uri::segment(6);

		// Get subject
		$subject = array();
		if ( $subjectID && !( $subject = $this->reports_subjects_model->getSubject($subjectID, false) ) )
		{
			view::setError(__('no_subject', 'reports_subjects'));
			router::redirect('cp/content/reports/subjects');
		}

		// Assign vars
		view::assign(array('subjectID' => $subjectID, 'subject' => $subject));

		// Process form values
		if ( input::post('do_save_subject') )
		{
			$this->_saveSubject($subjectID);
		}

		// Set title
		view::setTitle($subjectID ? __('subject_edit', 'reports_subjects') : __('subject_new', 'reports_subjects'));

		// Set trail
		view::setTrail('cp/content/reports/subjects/edit/' . ( $subjectID ? $subjectID : '' ), ( $subjectID ? __('subject_edit', 'reports_subjects') . ' - ' . text_helper::entities($subject['name']) : __('subject_new', 'reports_subjects') ));

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/content/reports/subjects/edit');
	}

	protected function _saveSubject($subjectID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = $input = array();

		// Get subject data
		$subjectData = array();
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $language )
		{
			$rules['name_' . $language] = array(
				'label' => __('name', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 255)
			);
			$input[] = 'name_' . $language;
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get subject data
		$subject = input::post($input);
		$subject['active'] = input::post('active') ? 1 : 0;

		// Save subject
		if ( !( $subjectID = $this->reports_subjects_model->saveSubject($subjectID, $subject) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('subject_saved', 'reports_subjects'));

		router::redirect('cp/content/reports/subjects/edit/' . $subjectID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/reports/subjects') ) return false;

		// Get URI vars
		$subjectID = (int)uri::segment(6);

		// Get subject
		if ( !$subjectID || !( $subject = $this->reports_subjects_model->getSubject($subjectID) ) )
		{
			view::setError(__('no_subject', 'reports_subjects'));
			router::redirect('cp/content/reports/subjects');
		}

		// Delete subject
		$this->reports_subjects_model->deleteSubject($subjectID, $subject);

		// Success
		view::setInfo(__('subject_deleted', 'reports_subjects'));

		router::redirect('cp/content/reports/subjects');
	}
}
