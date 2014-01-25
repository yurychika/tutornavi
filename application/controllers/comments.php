<?php

class Comments_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		loader::model('comments/comments');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse($resource = '', $itemID = 0, $total = false, $page = false, $post = true, $info = true, $static = false)
	{
		// Get vars
		if ( input::isAjaxRequest() && !$static )
		{
			$resource = input::post_get('resource');
			$itemID = (int)input::post_get('item_id');
			$post = (bool)input::post_get('post');
			$info = (bool)input::post_get('info');
		}

		$split = (int)input::post_get('split') && (int)input::post_get('split') <= config::item('comments_per_page', 'comments') ? (int)input::post_get('split') : config::item('comments_per_page', 'comments');

		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		if ( !$resourceID || !$itemID )
		{
			return false;
		}

		// Do we have the number of comments?
		if ( $total === false )
		{
			// Count comments
			$total = $this->comments_model->countComments(array(), array(), array('resource' => $resource, 'item_id' => $itemID));
		}

		// Post comment
		if ( input::post('do_save_comment') && session::permission('comments_post', 'comments') )
		{
			if ( $this->_saveComment($resource, $itemID) )
			{
				$total++;
			}
		}
		// Delete comment
		elseif ( input::post('delete') && session::permission('comments_delete', 'comments') )
		{
			if ( $this->_deleteComment($resource, $itemID, (int)input::post('delete')) )
			{
				$total--;
			}
		}

		// Current page
		$page = $page ? $page : (int)input::post_get('page', 1);
		$page = $page > 0 ? $page : 1;

		// Limit
		$limit = ( ( $page - 1 ) * $split ) . ', ' . $split;

		// Get comments
		$comments = $this->comments_model->getComments($resource, $itemID, array(), '`c`.`post_date` desc', $limit);

		// If no comments were found, try to fetch from from the previous page
		if ( !$comments && $page > 1 )
		{
			$page--;

			// Limit
			$from = ( $page - 1 ) * $split;
			$limit = $from . ', ' . $split;

			$comments = $this->comments_model->getComments($resource, $itemID, array(), '`c`.`post_date` desc', $limit);
		}

		// Pagination config
		$config = array(
			'base_url' => 'comments/browse?',
			'total_items' => $total,
			'items_per_page' => $split,
			'current_page' => $page,
			'uri_segment' => 'page',
			'link_attr' => array('onclick' => "runAjax(this.href,{'resource':'" . $resource . "','item_id':" . $itemID . ",'split':" . $split . ",'post':" . ( $post ? 1 : 0 ) . ",'info':" . ( $info ? 1 : 0 ) . "},'replaceContent','comments-container-" . $resource . "-" . $itemID . "','pagination-" . $resource . "-" . $itemID . "');return false;"),
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array(
			'resource' => $resource,
			'itemID' => $itemID,
			'comments' => $comments,
			'pagination' => $pagination,
			'split' => $split,
			'post' => ( users_helper::isLoggedin() && $post ? true : false ),
			'info' => $info
		), '', 'comments/index');

		if ( input::isAjaxRequest() && !$static )
		{
			$output = view::load('comments/index', array(), true);

			view::ajaxResponse($output);
		}
		else
		{
			view::load('comments/index');
		}
	}

	protected function _saveComment($resource, $itemID)
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			return false;
		}

		// Create rules
		$rules = array(
			'comment' => array(
				'label' => __('comment_body', 'comments'),
				'rules' => array('trim', 'required', 'min_length' => config::item('min_length', 'comments'), 'max_length' => config::item('max_length', 'comments'), 'callback__is_comments_delay'),
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get comment
		$comment = array('comment' => input::post('comment'));

		// Get table and column names
		$table = config::item('resources', 'core', $resource, 'table');
		$column = config::item('resources', 'core', $resource, 'column');
		$user = config::item('resources', 'core', $resource, 'user');

		// Get resource item
		$item = $this->db->query("SELECT `" . $column . "` " . ( $user ? ', `' . $user . '` AS `user_id`' : '' ) . "
			FROM `:prefix:" . $table . "`
			WHERE `" . $column . "`=? LIMIT 1",
			array($itemID))->row();

		// Does resource exist?
		if ( !$item )
		{
			return false;
		}

		// Save comment
		if ( !$this->comments_model->saveComment(0, $comment, $resource, ( isset($item['user_id']) ? $item['user_id'] : 0 ), $itemID) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Reset post values
		$_POST['comment'] = '';
		validate::resetRules();

		return true;
	}

	protected function _deleteComment($resource, $itemID, $commentID)
	{
		// Validate comment ID
		if ( !$commentID || $commentID <= 0 )
		{
			return false;
		}

		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			return false;
		}

		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		// Get comment
		$comment = $this->comments_model->getComment($commentID);

		// Does resource exist and valid for deletion?
		if ( !$comment || $comment['resource_id'] != $resourceID || $comment['item_id'] != $itemID || ( $comment['poster_id'] != session::item('user_id') && $comment['user_id'] != session::item('user_id') ) )
		{
			return false;
		}

		// Delete comment
		if ( !$this->comments_model->deleteComment($commentID, $resource, $comment['user_id'], $comment['poster_id'], $itemID) )
		{
			return false;
		}

		return true;
	}

	public function like()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			view::ajaxError(__('no_login', 'system_info'), 403);
		}

		// Get vars
		$resource = input::post_get('resource');
		$itemID = (int)input::post_get('item_id');
		$like = (int)input::post_get('like') ? 1 : 0;

		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		if ( !$resourceID || !$itemID )
		{
			return false;
		}

		// Load likes model
		loader::model('comments/likes');

		// Get resource item and like if exists
		$item = $this->likes_model->getResourceLike($resource, $itemID);

		// Do resource or like exist?
		if ( !$item || $item['post_date'] && $like || !$item['post_date'] && !$like )
		{
			return false;
		}

		// Save like
		if ( !$this->likes_model->saveLike($resource, ( isset($item['user_id']) ? $item['user_id'] : 0 ), $itemID, $like) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		$params = array(
			'resource' => $resource,
			'itemID' => $itemID,
			'likes' => $like ? $item['total_likes'] + 1 : $item['total_likes'] - 1,
			'liked' => $like ? 1 : 0,
			'date' => date_helper::now(),
		);

		$output = view::load('comments/likes', $params, true);

		view::ajaxResponse($output);
	}

	public function vote()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			view::ajaxError(__('no_login', 'system_info'), 403);
		}

		// Get vars
		$resource = input::post_get('resource');
		$itemID = (int)input::post_get('item_id');
		$score = (int)input::post_get('score');

		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		if ( !$resourceID || !$itemID || $score < 1 || $score > 5 )
		{
			return false;
		}

		// Load votes model
		loader::model('comments/votes');

		// Get resource item and vote if exists
		$item = $this->votes_model->getResourceVote($resource, $itemID);

		// Do resource or vote exist?
		if ( !$item || $item['post_date'] )
		{
			return false;
		}

		// Save vote
		if ( !$this->votes_model->saveVote($resource, ( isset($item['user_id']) ? $item['user_id'] : 0 ), $itemID, $score) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		$rating = number_format( ( $item['total_score'] + $score ) / ( $item['total_votes'] + 1 ), 2);

		$params = array(
			'resource' => $resource,
			'itemID' => $itemID,
			'votes' => ( $item['total_votes'] + 1 ),
			'score' => $item['total_score'],
			'rating' => $rating,
			'voted' => $score,
			'date' => date_helper::now(),
		);

		$output = view::load('comments/rating', $params, true);

		view::ajaxResponse($output);
	}

	public function _is_comments_delay()
	{
		if ( session::permission('comments_delay_limit', 'comments') )
		{
			$comments = $this->comments_model->countRecentComments();

			if ( $comments >= session::permission('comments_delay_limit', 'comments') )
			{
				validate::setError('_is_comments_delay', __('comments_delay_reached', 'comments', array(
					'%comments' => session::permission('comments_delay_limit', 'comments'),
					'%time' => session::permission('comments_delay_time', 'comments'),
					'%type' => utf8::strtolower(__(( session::permission('comments_delay_type', 'comments') == 'minutes' ? 'minute' : 'hour' ) . ( session::permission('comments_delay_time', 'comments') > 1 ? 's' : '' ), 'date'))
					)));
				return false;
			}
		}

		return true;
	}
}