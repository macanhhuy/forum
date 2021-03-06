<?php

namespace Phosphorum\Controllers;

use Phosphorum\Models\Posts,
	Phosphorum\Models\PostsReplies,
	Phalcon\Http\Response;

class RepliesController extends \Phalcon\Mvc\Controller
{

	public function initialize()
	{
		$this->view->disable();
	}

	/**
	 * Returs the raw comment as it as edited
	 *
	 * @param int $id
	 */
	public function getAction($id)
	{

		$response = new Response();

		$usersId = $this->session->get('identity');
		if (!$usersId) {
			$response->setStatusCode('401', 'Unauthorized');
			return $response;
		}

		$postReply = PostsReplies::findFirst(array(
			'id = ?0 AND users_id = ?1',
			'bind' => array($id, $usersId)
		));
		if ($postReply) {
			$data = array('status' => 'OK', 'id' => $postReply->id, 'comment' => $postReply->content);
		} else {
			$data = array('status' => 'ERROR');
		}

		$response->setContent(json_encode($data));
		return $response;
	}

	/**
	 * Updates a reply
	 */
	public function updateAction()
	{

		$usersId = $this->session->get('identity');
		if (!$usersId) {
			return $this->response->redirect();
		}

		if (!$this->request->isPost()) {
			return $this->response->redirect();
		}

		$postReply = PostsReplies::findFirst(array(
			'id = ?0 AND users_id = ?1',
			'bind' => array($this->request->getPost('id'), $usersId)
		));
		if (!$postReply) {
			return $this->response->redirect();
		}

		$content = $this->request->getPost('content');
		if (trim($content)) {
			$postReply->content = $content;
			$postReply->save();
		}

		return $this->response->redirect('discussion/' . $postReply->post->id . '/' . $postReply->post->slug . '#C' . $postReply->id);
	}

	/**
	 * Deletes a reply
	 *
	 * @param int $id
	 */
	public function deleteAction($id)
	{

		$usersId = $this->session->get('identity');
		if (!$usersId) {
			return $this->response->setStatusCode('401', 'Unauthorized');
		}

		$postReply = PostsReplies::findFirst(array(
			'id = ?0 AND users_id = ?1',
			'bind' => array($id, $usersId)
		));
		if ($postReply) {

			if ($postReply->delete()) {
				if ($usersId != $postReply->post->users_id) {
					$postReply->post->number_replies--;
					$postReply->post->save();
				}
			}

			return $this->response->redirect('discussion/' . $postReply->post->id . '/' . $postReply->post->slug);
		}

		return $this->response->redirect();
	}

}