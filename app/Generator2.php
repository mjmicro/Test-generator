<?php

namespace App;

use Illuminate\Support\Facades\Config;

class Generator2
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
        $prompts = $this->getPrompts();
        $inputs =  $this->getInputs($prompts);
              // Ask the user a question
              $userQuestion = "Generate feature test class code using Laravel 8 passport for the $controllerName using the given code: \n $controllerContent";

              // Get the embedding for the question
              $question =  json_decode(json_encode(['embeddings' => json_decode( $this->openAiEmbeddingsCreate([
                  'model' => 'text-embedding-ada-002',
                  'input' => [
                      $userQuestion,
                  ]
                  ]))->data]));
      
              // Take the question and compare it to the prompts
              $answer =  $this->getAnswer($prompts, $inputs, $question);
              $davanci = "Rewrite the question and give the answer with an example in PHP using given Example
              Example: {$prompts[$answer['index']]}
              Question: {$userQuestion}
              Answer:";

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
        $res = strstr($res, '<?php');
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
            if (str_contains($matches[$i][0], '/Models/') || str_contains($matches[$i][0], '/Resources/')|| str_contains($matches[$i][0], '/Requests/')) {
                array_push($files, $matches[$i][0]);
            }
        }
        return $files;
    }

    public static function getAnswer($prompts, $inputs, $question)
    {
        // loops throuogh all the inputs and compare on a cosine similarity to the question and output the correct answer
        $results = [];
        for ($i = 0; $i < count($inputs->embeddings); $i++) {
            $similarity = self::cosineSimilarity($inputs->embeddings[$i]->embedding, $question->embeddings[0]->embedding);
            // store the simliarty and index in an array and sort by the similarity
            $results[] = [
                'similarity' => $similarity,
                'index' => $i,
                'input' => $prompts[$i],
            ];
        }
        usort($results, function ($a, $b) {
            return $a['similarity'] <=> $b['similarity'];
        });

        return end($results);
    }

    public static function cosineSimilarity($u, $v)
    {
        $dotProduct = 0;
        $uLength = 0;
        $vLength = 0;
        for ($i = 0; $i < count($u); $i++) {
            $dotProduct += $u[$i] * $v[$i];
            $uLength += $u[$i] * $u[$i];
            $vLength += $v[$i] * $v[$i];
        }
        $uLength = sqrt($uLength);
        $vLength = sqrt($vLength);
        return $dotProduct / ($uLength * $vLength);
    }

    public static  function getInputs($prompts)
    {
       return json_decode(json_encode(['embeddings' => json_decode(self::openAiEmbeddingsCreate([
            'model' => 'text-embedding-ada-002',
            'input' => $prompts,
        ]))->data]));
    }

    public static function getPrompts()
    {
        return 
            
            self::phpPrompts()
        ;
    }

   public static function openAiCompletionCreate($data)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.openai.com/v1/completions',
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

        curl_close($curl);
        return $response;
    }

    public static function openAiEmbeddingsCreate($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.openai.com/v1/embeddings',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . Config::get('app.ChatGptKey'),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public static function phpPrompts(){
        $text1 = <<<'EOT'
        <?php

namespace Tests\Feature\API\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations, WithFaker;
    public function setUp() : void {
                parent::setUp();
                $this->artisan('passport:install');
        }
    public function test_user_can_register()
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $this->faker->password
        ];

        $response = $this->postJson('/api/register', $data);

        $response
            ->assertStatus(201);
            // ->assertJsonStructure([
            //     'user' => [
            //         'id',
            //         'name',
            //         'email',
            //         'created_at',
            //         'updated_at'
            //     ],
            //     'access_token',
            // ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create(['password' => bcrypt($password = $this->faker->password)]);

        $data = [
            'email' => $user->email,
            'password' => $password
        ];

        $response = $this->postJson('/api/login', $data);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'access_token',
            ]);
    }

    public function test_user_can_logout()
    {
        Passport::actingAs(User::factory()->create(), ['*']);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)->assertJson(['message' => 'Logged out']);
    }
}
EOT;
$text2 = <<<'EOT'
`ProductControllerTest: 
class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::latest()->paginate(10);
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $product = Product::create($data);
        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $product->update($request->all());
        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return new ProductResource($product);
    }

    /**
     * Search for a name
     *
     * @param  str  $name
     * @return \Illuminate\Http\Response
     */
    public function search($title)
    {
        $products = Product::where('title', 'like', '%'.$title.'%')->get();
        return ProductResource::collection($products);
    }
}
`.
EOT;
        return [''. $text1, ''. $text2];
    }
    
}
