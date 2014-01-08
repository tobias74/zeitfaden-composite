<?php 

class GroupController extends AbstractZeitfadenController
{
	
	protected function declareActionsThatNeedLogin()
	{
		return array(
			'getById',
			'create',
			'search',
			'setDescription',
			'getAll'
		);
	}
	
	public function getByIdAction()
	{
		$userId = $this->_request->getParam('userId',0);
		$groupId = $this->_request->getParam('groupId',0);
		
		$group = $this->getService()->getGroupById($groupId, $userId);
		
		$groupDTO = $this->getService()->getGroupDTO($group);
		
		$this->_response->appendValue('group', $groupDTO);
	}
	
	
	
	public function createAction()
	{
		$group = $this->getService()->createGroup();
		$group->setUserId($this->getLoggedInUser()->getId());
		
		$group->setDescription($this->_request->getParam('description',''));
		
		$this->getService()->mergeGroup($group);
		
		$this->_response->appendValue('group', $this->getService()->getGroupDTO($group));
		
	}
	
	
	public function deleteAction()
	{
		$userId = $this->loggedInUser->getId();
		$groupId = $this->_request->getParam('groupId',0);
		
		$group = $this->getService()->getGroupById($groupId, $userId);
		
		$this->getService()->deleteGroup($group);
	}
	
	
	public function getAllAction()
	{
		$groups = $this->getService()->getGroupsByOwner($this->getLoggedInUser());
		
		$this->_response->appendValue('allGroups', $this->getService()->getGroupsDTOs($groups));
		
		
	}
	
	public function setDescriptionAction()
	{
		$userId = $this->loggedInUser->getUserId();
		$groupId = $this->_request->getParam('groupId',0);
		$description = $this->_request->getParam('description','');
		
		$group = $this->getService()->getGroupById($groupId, $userId);
		$group->setDescription($description);
		$this->getService()->mergeGroup($group);

		$this->_response->appendValue('group', $this->getService()->getGroupDTO($group));
		
	}
	
}



				
				
				
				
				
				
				
				
				
				