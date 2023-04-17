<?php

namespace App;

use Illuminate\Console\Command;
// use OpenAI\Laravel\Facades\OpenAI;

class TestGeneratorCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embedding:open';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Example of OpenAI emmbeded in Laravel';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // The data that we want to train the model on
        // This is what we would insert into the database to be able to
        // run the question on without having to pay for the tokens
        $prompts = self::getPrompts();

        // Send the prompts to the OpenAI API to get the embeddings
        $inputs = self::getInputs($prompts);
        // dd($inputs->embeddings);

        // Ask the user a question
        $userQuestion = self::ask('What is your question?');

        // Get the embedding for the question
        $question =  json_decode(json_encode(['embeddings' => json_decode(self::openAiEmbeddingsCreate([
            'model' => 'text-embedding-ada-002',
            'input' => [
                $userQuestion,
            ]
            ]))->data]));

        // Take the question and compare it to the prompts
        $answer = self::getAnswer($prompts, $inputs, $question);
        // Output the answer
        $this->info('the ada match: ' . $prompts[$answer['index']]);
        // Get a prompt to send to the davanci model
        $davanci = "Rewrite the question and give the answer with an example in PHP from the context
        Example: {$prompts[$answer['index']]}
        Question: {$userQuestion}
        Answer:";
        // Send the prompt to the davanci model
        $result =  json_decode(self::openAiCompletionCreate([
            'model' => 'text-davinci-003',
            'prompt' => $davanci,
            'temperature' => 0.9,
            'max_tokens' => 2000,
        ]));
        // Output the result
        $this->info("Make it humanish: {$result->choices[0]->text}");
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
                'Authorization: Bearer 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',
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
                'Authorization: Bearer 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',
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
