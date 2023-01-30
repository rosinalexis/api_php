<?php

class TaskController
{

    public function __construct(
        private TaskGateway $taskGateway,
        private int $user_id
    )
    {

    }
    public function processRequest(string $method, ?string $id): void
    {

        if ($id === null) {
            if ($method == "GET") {
                $result = $this->taskGateway->getAllForUser($this->user_id);
                echo json_encode($result);

            } elseif ($method == "POST") {
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    $this->responseUnprocessableEntity($errors);
                    return;
                }

                $id = $this->taskGateway->create($data);

                $this->responseCreated($id);
            } else {
                $this->responseMethodNotAllowed("GET,POST");
            }
        } else {

            if ($method == "POST") {
                $this->responseMethodNotAllowed("GET,PUT,PATCH,DELETE");
                return;
            }

            $task = $this->taskGateway->get($id);

            if ($task === false) {
                $this->respondNotFound($id);
                return;
            }

            switch ($method) {
                case "GET":
                    echo json_encode($task);
                    break;

                case "PATCH":
                    $data = (array) json_decode(file_get_contents("php://input"), true);

                    $errors = $this->getValidationErrors($data);

                    if (!empty($errors)) {
                        $this->responseUnprocessableEntity($errors);
                        return;
                    }

                    $rows = $this->taskGateway->update($id, $data);
                    echo json_encode([
                        "message" => "Task updated",
                        "row" => $rows,
                    ]);
                    break;

                case "DELETE":
                    $rows = $this->taskGateway->delete($id);

                    echo json_encode([
                        "message" => "Task deleted",
                        "rows" => $rows
                    ]);

                    break;

                default:
                    $this->responseMethodNotAllowed("GET,PATCH,DELETE");
            }
        }
    }

    private function responseUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
    }

    private function responseMethodNotAllowed(string $allowed_method): void
    {
        http_response_code(405);
        header("Allow: $allowed_method");
    }

    private function respondNotFound(string $id): void
    {
        http_response_code(404);
        echo json_encode([
            "message" => "Task with ID $id not found"
        ]);
    }

    private function responseCreated(string $id): void
    {
        http_response_code(201);
        echo json_encode([
            "id" => $id,
            "message" => "Task created"
        ]);
    }

    private function getValidationErrors(array $data): array
    {
        $errors = [];
        if (empty($data["name"])) {
            $errors[] = "name is required";
        }

        if (!empty($data["priority"])) {
            if (filter_var($data["priority"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "priority must be an integer";
            }
        }

        return $errors;
    }


}