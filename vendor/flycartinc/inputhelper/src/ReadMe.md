**How to use?**
Add the code as required
use FlycartInput\FInput;
or
require_once 'FlycartInput/FInput.php';


**To get an input value:**

$postData = FInput::getInstance();
$page_id = $postData->get('page_id'));
