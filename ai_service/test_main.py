import json
import os
import unittest
from unittest.mock import patch

from fastapi import HTTPException

import main


class KnowledgeRetrievalTests(unittest.TestCase):
    def test_grounding_evaluation_cases(self):
        cases = [
            ("society", "Where is Yadav Living Society?", "society-location"),
            ("society", "How do I submit Resident KYC?", "society-kyc"),
            ("society", "Where do I see my maintenance bill?", "society-bills"),
            ("society", "Where can I read community notices?", "society-announcements"),
            ("pg", "Where is Yadav Living PG?", "pg-location"),
            ("pg", "How do I submit a maintenance complaint?", "pg-complaints"),
            ("pg", "Where can I view payment history?", "pg-fees"),
            ("pg", "How do I submit my move-out notice?", "pg-vacate"),
        ]
        for property_name, question, expected_source in cases:
            with self.subTest(property=property_name, question=question):
                documents = main.retrieve_documents(property_name, question)
                self.assertTrue(documents)
                self.assertEqual(expected_source, documents[0]["id"])

    def test_society_question_returns_society_location_only(self):
        documents = main.retrieve_documents("society", "Where is the Society located?")
        self.assertTrue(documents)
        self.assertEqual(["society-location"], [document["id"] for document in documents[:1]])
        self.assertNotIn("pg-location", [document["id"] for document in documents])

    def test_pg_payment_question_returns_verified_payment_guidance(self):
        documents = main.retrieve_documents("pg", "How do I check my payment history?")
        self.assertEqual("pg-fees", documents[0]["id"])

    def test_unrelated_question_has_no_context(self):
        self.assertEqual([], main.retrieve_documents("society", "What is the weather on Mars?"))

    def test_detects_personal_contact_and_identity_data(self):
        self.assertTrue(main.contains_sensitive_data("Call me at +91 9876543210"))
        self.assertTrue(main.contains_sensitive_data("My email is resident@example.com"))
        self.assertTrue(main.contains_sensitive_data("Aadhaar 1234 5678 9012"))
        self.assertFalse(main.contains_sensitive_data("Where is Yadav Living Society?"))


class ProviderTests(unittest.TestCase):
    def test_missing_model_key_is_explicit(self):
        with patch.dict(os.environ, {"OPENAI_API_KEY": ""}, clear=False):
            with self.assertRaises(HTTPException) as raised:
                main.generate_answer("society", "Where is the Society?", [])
        self.assertEqual(503, raised.exception.status_code)

    def test_provider_receives_only_one_question_and_curated_context(self):
        document = {
            "id": "society-location",
            "title": "Society location",
            "content": "Verified address",
            "url": "/HMS-2/home/home.php",
        }

        class FakeResponse:
            def __enter__(self):
                return self

            def __exit__(self, *_args):
                return False

            def read(self):
                return json.dumps(
                    {"choices": [{"message": {"content": "The Society address is verified."}}]}
                ).encode("utf-8")

        with patch.dict(os.environ, {"OPENAI_API_KEY": "test-key"}, clear=False):
            with patch.object(main.urllib.request, "urlopen", return_value=FakeResponse()) as urlopen:
                answer = main.generate_answer("society", "Where is the Society?", [document])

        payload = json.loads(urlopen.call_args.args[0].data.decode("utf-8"))
        self.assertEqual("The Society address is verified.", answer)
        self.assertEqual(["system", "user"], [message["role"] for message in payload["messages"]])
        self.assertEqual("Where is the Society?", payload["messages"][1]["content"])
        self.assertIn("Verified address", payload["messages"][0]["content"])

    def test_chat_rejects_missing_internal_service_token(self):
        with patch.dict(os.environ, {"AI_SERVICE_TOKEN": ""}, clear=False):
            with self.assertRaises(HTTPException) as raised:
                main.chat(main.ChatRequest(property="society", question="Where is the Society?"))
        self.assertEqual(503, raised.exception.status_code)

    def test_chat_blocks_personal_data_before_provider_call(self):
        with patch.dict(os.environ, {"AI_SERVICE_TOKEN": "test-token"}, clear=False):
            with self.assertRaises(HTTPException) as raised:
                main.chat(
                    main.ChatRequest(property="pg", question="Call me at +91 9876543210"),
                    "Bearer test-token",
                )
        self.assertEqual(400, raised.exception.status_code)

    def test_chat_answers_unmatched_question_without_calling_provider(self):
        with patch.dict(os.environ, {"AI_SERVICE_TOKEN": "test-token"}, clear=False):
            result = main.chat(
                main.ChatRequest(property="society", question="What is the weather on Mars?"),
                "Bearer test-token",
            )
        self.assertEqual([], result["sources"])
        self.assertIn("verified information", result["answer"])


if __name__ == "__main__":
    unittest.main()
