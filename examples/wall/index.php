<?php
/**
 * This example shows how to implement a wall / comment stream via the equify SDK
 * You need a valid API key with user or admin access to the connected equify instance.
 * Edit the config.php file and add your API key and secret.
 */

use equifySDK\Request;

require(__DIR__.'/../config.php');

// If your API key is running in admin mode, set the user ID here that should be used to create new projects or write comments
$apiUserID = 2;

if ($_POST)
{
	// Load existing project or create new one
	$projectID = $_REQUEST['projectID'];
	if ($projectID)
	{
		$projectRequest = new Request('projects/'.$projectID);
		$projectResponse = $projectRequest->send();
		$project = $projectResponse->getData('project');
		// Create the wall module (if it does not exist yet)
		if ($project && $_POST['createwall'])
		{
			$projectID = $project->id;
			// Check if the wall module already exists
			$modCheckRequest = new Request('projects/' . $projectID . '/modules', Request::METHOD_GET);
			$modCheckRequest->setParam('moduleTag', 'wall');
			$modCheckResult = $modCheckRequest->send();
			$modules = $modCheckResult->getData('modules');
			if (count($modules) == 0)
			{
				// Create module category
				$modCatRequest = new Request('projects/'.$projectID.'/moduleCategories', Request::METHOD_POST);
				$params = array('title' => 'Public posts');
				$modCatRequest->setParams($params);
				if ($apiUserID) $modCatRequest->setParam('actAsUserID', $apiUserID);
				$modCatResponse = $modCatRequest->send();
				$modCatData = $modCatResponse->getData('moduleCategory');
				if ($modCatData) $modCatID = $modCatData->id;
				if (!empty($modCatID))
				{
					// Create module for wall posts
					$moduleRequest = new Request('projects/'.$projectID.'/modules', Request::METHOD_POST);
					$params = array('title' => 'Wall posts', 'moduleCategoryID' => $modCatID, 'moduleTag' => 'wall');
					$moduleRequest->setParams($params);
					if ($apiUserID) $moduleRequest->setParam('actAsUserID', $apiUserID);
					$moduleResponse = $moduleRequest->send();
				}
			}
		}
		if ($project && $_POST['sendwallentry'])
		{
			$sendMessageRequest = new Request('projects/'.$projectID.'/messages', Request::METHOD_POST);
			$sendMessageRequest->setParams(array('text' => $_REQUEST['wallentrytext'], 'moduleTag' => 'wall'));
			if ($apiUserID) $sendMessageRequest->setParam('actAsUserID', $apiUserID);
			$sendMessageResponse = $sendMessageRequest->send();
		}
		// Load messages from this project from the special comment stream module
		$messageRequest = new Request('projects/'.$projectID.'/messages');
		$messageRequest->setParam('moduleTag', 'wall');
		$messageResponse = $messageRequest->send();
		$messages = $messageResponse->getData('messages');
	}
}
if (empty($messages)) {
    $messages = array();
}
// Load all projects for the select box
$allProjectsRequest = new Request('projects');
$allProjectsResponse = $allProjectsRequest->send();
// Use projects from response
$projects = $allProjectsResponse->getData('projects');
?>
<html>
	<head>
	</head>
	<body>
		<div><strong>Projects</strong></div>
		<form method="post" action="">
			<select name="projectID">
				<option value=""><em>Create new</em></option>
				<?php
				foreach ($projects as $curProject)
				{
                    ?>
                    <option value="<?php echo $curProject->id; ?>"
                            <?php if (isset($projectID) && $projectID == $curProject->id) { ?>selected="selected"<?php } ?>>
                        <?php echo htmlspecialchars($curProject->title); ?>
                    </option>
                    <?php
                }
                ?>
			</select>
			<button type="submit" name="selectproject" value="selectproject">Select</button>
			<button type="submit" name="createwall" value="createwall">Create wall</button>
		</form>
		<br/>
		<br/>
		<?php
		if (isset($projectID))
		{
			?>
			<div><strong>Stream</strong></div>
			<?php
			foreach ($messages as $curMessage)
			{
				?>
				<div><?php echo $curMessage->subject; ?></div>
				<div><?php echo $curMessage->text; ?></div>
				<?php
			}
			?>
			<form method="post" action="">
				<input type="hidden" name="projectID" value="<?php echo $projectID; ?>" />
				<textarea name="wallentrytext"></textarea>
				<button type="submit" name="sendwallentry" value="send">Send</button>
			</form>
			<?php
		}
		?>
	</body>
</html>
