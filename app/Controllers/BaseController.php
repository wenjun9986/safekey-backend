<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Services;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }

    public function getResponse(array $responseBody, int $code = ResponseInterface::HTTP_OK)
    {
        return $this
            ->response
            ->setStatusCode($code)
            ->setJSON($responseBody);
    }

    private function get_code_message($code = null) {
        $constants = get_defined_constants();
        return isset($constants[$code]) ? $constants[$code] : $code;
    }

    public function getRequestInput(IncomingRequest $request)
    {
        $input = (empty($request->getPost()) ? $request->getGet() : $request->getPost());
        if (empty($input)) {
            //convert request body to associative array
            $input = $request->getBody() ? json_decode($request->getBody(), true) : [];
        }
        return $input;
    }

    public function validateRequest($input, array $rules, array $messages =[])
    {
        $this->validator = Services::Validation()->setRules($rules);
        // If you replace the $rules array with the name of the group
        if (is_string($rules)) {
            $validation = config('Validation');

            // If the rule wasn't found in the \Config\Validation, we
            // should throw an exception so the developer can find it.
            if (!isset($validation->$rules)) {
                throw ValidationException::forRuleNotFound($rules);
            }

            // If no error message is defined, use the error message in the Config\Validation file
            if (!$messages) {
                $errorName = $rules . '_errors';
                $messages = $validation->$errorName ?? [];
            }

            $rules = $validation->$rules;
        }
        return $this->validator->setRules($rules, $messages)->run($input);
    }

    public function success($messages, int $status = 200, string $code = null, string $customMessage = '') {
        if (!is_array($messages)) {
            $messages = ['error' => $messages];
        }

        $response = [
            'status' => "success",
            'status_code' => $messages['code'] ?? $code ?? 'OK',
            'data' => $messages['data'] ?? $messages,
            'date' => date('Y-m-d H:i:s')
        ];
        if (isset($messages['message'])){
            $response['message'] = $messages['message'];
        }
        return $this->getResponse($response,$status);
    }

    public function fails($messages, int $status = 400, string $code = null, string $customMessage = 'error') {

        if (!is_array($messages)) {
            $messages = ['error' => $messages];
        }

        $customMessage = isset($messages['error']) ? $this->get_code_message($messages['error']) : $messages['data'];

        $response = [
            'status' => "error",
            'status_code' => $status,
            'message' => $customMessage,
            'date' => date('Y-m-d H:i:s')
        ];
        if (isset($messages['data'])){
            $response['data'] = $messages['data'];
        }
        return $this->getResponse($response,$status);
    }


}
