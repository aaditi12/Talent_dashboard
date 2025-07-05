<?php
// Replace with your actual OpenAI API key
$apiKey = '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';

$input = json_decode(file_get_contents("php://input"), true);
$taskData = json_encode($input['tasks'], JSON_PRETTY_PRINT);

$prompt = "Analyze the following task performance data and provide a summary report:\n\n$taskData";

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
]);

$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a performance analyst chatbot.'],
        ['role' => 'user', 'content' => $prompt],
    ],
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);
$reply = $responseData['choices'][0]['message']['content'] ?? 'Error in analysis.';

echo json_encode(['analysis' => $reply]);
?>
