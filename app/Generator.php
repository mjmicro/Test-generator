<?php

namespace App;

use Illuminate\Support\Facades\Config;

class Generator
{
    protected $filter;
    protected $directory;
    protected $destinationFilePath;

    /**
     * Initiate the global parameters
     *
     * @param array $options
     */
    public function __construct($options)
    {
        $this->filter = $options['filter'];
        $this->directory = $options['directory'];
        $this->destinationFilePath = base_path('tests/Feature/' . $this->directory);
    }

    /**
     * Generate the route methods and write to the file
     *
     * @return void
     */
    public function generate()
    {
       


        $this->createDirectory();
        $controllerName = $this->filter;
        $controllerContent = file_get_contents("app/Http/Controllers/API/$controllerName.php");

        $files = $this->getfiles(file_get_contents("app/Http/Controllers/API/$controllerName.php"));
        for ($i = 0; $i < count($files); $i++) {
            $name = $files[$i];
            $controllerContent .= file_get_contents("$name.php") . PHP_EOL;
        }
        // $controllerContent = json_encode($controllerContent);
        $prompts = TestGenerator2::getPrompts();
        $inputs = TestGenerator2::getInputs($prompts);
              // Ask the user a question
              $userQuestion = "Generate feature test class code using Laravel passport for the $controllerName using the given code: \n $controllerContent";

              // Get the embedding for the question
              $question =  json_decode(json_encode(['embeddings' => json_decode(TestGenerator2::openAiEmbeddingsCreate([
                  'model' => 'text-embedding-ada-002',
                  'input' => [
                      $userQuestion,
                  ]
                  ]))->data]));
      
              // Take the question and compare it to the prompts
              $answer = TestGenerator2::getAnswer($prompts, $inputs, $question);
              $davanci = "Rewrite the question and give the answer with an example in PHP using given Example
              Example: {$prompts[$answer['index']]}
              Question: {$userQuestion}
              Answer:";
              // Send the prompt to the davanci model
            //   $res =  json_decode(TestGenerator2::openAiCompletionCreate([
            //       'model' => 'text-davinci-003',
            //       'prompt' => $davanci,
            //       'temperature' => 0.9,
            //       'max_tokens' => 2000,
            //   ]))->choices[0]->text;

        $data =
            [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' =>
                        $davanci
                        // "Generate feature test code using Laravel passport for the following Controller: \n $controllerContent"

                    ]
                ],
                'temperature' => 0.9
            ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . Config::get('app.ChatGptKey'),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $res = json_decode($response)->choices[0]->message->content;
        curl_close($curl);
        if (str_contains($res, '```')) {
            $re = '/(?<=``` ).+(?=```)/';
            preg_match_all($re, $res, $matches, PREG_SET_ORDER, 0);

            $res = isset($matches[0][0])??$res;
        }

        


        $this->writeToFile(
            "$controllerName" . "Test",
            $res
            // json_decode($response)->choices[0]->message->content
        );
    }

    /**
     * Write the string into the file
     *
     * @param string $controllerName
     * @param string $$content
     * @return void
     */
    protected function writeToFile($controllerName, $content)
    {
        $fileName = $this->destinationFilePath . '/' . $controllerName . '.php';
        $file = fopen($fileName, 'w');
        fwrite($file, $content . PHP_EOL);
        fclose($file);

        echo "\033[32m" . basename($fileName) . ' Created Successfully' . PHP_EOL;
    }


    /**
     * Create a new directory if not exist
     *
     * @return void
     */
    protected function createDirectory()
    {
        $dirName = $this->destinationFilePath;
        if (!is_dir($dirName)) {
            mkdir($dirName, 0755, true);
        }
    }

    /**
     * Return's the controller name
     *
     * @param string $controller
     * @return string
     */
    protected function getControllerName($controller)
    {
        $namespaceReplaced = substr($controller, strrpos($controller, '\\') + 1);
        $actionNameReplaced = substr($namespaceReplaced, 0, strpos($namespaceReplaced, '@'));
        $controllerReplaced = str_replace('Controller', '', $actionNameReplaced);
        $controllerNameArray = preg_split('/(?=[A-Z])/', $controllerReplaced);
        $controllerName = trim(implode('', $controllerNameArray));

        return $controllerName;
    }

    public function getfiles($str)
    {
        $re = '/(?<=use ).+(?=;)/';
        $files = [];
        preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

        for ($i = 0; $i < count($matches); $i++) {
            $matches[$i][0] = lcfirst(str_replace("\\", "/", $matches[$i][0]));
            if (str_contains($matches[$i][0], '/Models/') || str_contains($matches[$i][0], '/Resources/')) {
                array_push($files, $matches[$i][0]);
            }
        }
        return $files;
    }
    
}
