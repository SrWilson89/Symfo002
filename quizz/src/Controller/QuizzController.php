<?php

namespace App\Controller;

use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class QuizzController extends AbstractController
{
    #[Route('/quizz', name: 'app_quizz_start')]
    public function start(SessionInterface $session, QuestionRepository $questionRepository): Response
    {
        // Reinicia la puntuación y el progreso del quizz
        $session->set('score', 0);
        $questions = $questionRepository->findAll();
        $session->set('questions', array_column($questions, 'id'));

        return $this->redirectToRoute('app_quizz_question', ['id' => 1]);
    }

    #[Route('/quizz/results', name: 'app_quizz_end')]
    public function showResults(SessionInterface $session, QuestionRepository $questionRepository): Response
    {
        $score = $session->get('score', 0);
        $totalQuestions = count($questionRepository->findAll());
        $session->clear();

        return $this->render('quizz/results.html.twig', [
            'score' => $score,
            'totalQuestions' => $totalQuestions,
        ]);
    }

    #[Route('/quizz/{id}', name: 'app_quizz_question')]
    public function showQuestion(int $id, QuestionRepository $questionRepository): Response
    {
        $question = $questionRepository->find($id);

        if (!$question) {
            return $this->redirectToRoute('app_quizz_end');
        }

        return $this->render('quizz/question.html.twig', [
            'question' => $question,
            'question_number' => $id,
        ]);
    }

    #[Route('/quizz/answer/{id}', name: 'app_quizz_answer', methods: ['POST'])]
    public function answer(int $id, Request $request, SessionInterface $session, AnswerRepository $answerRepository, QuestionRepository $questionRepository): Response
    {
        $submittedAnswerId = $request->request->get('answer');
        $answer = $answerRepository->find($submittedAnswerId);

        if ($answer && $answer->isCorrect()) {
            $currentScore = $session->get('score', 0);
            $session->set('score', $currentScore + 1);
        }

        $nextQuestionId = $id + 1;
        $totalQuestions = count($questionRepository->findAll());

        if ($nextQuestionId <= $totalQuestions) {
            return $this->redirectToRoute('app_quizz_question', ['id' => $nextQuestionId]);
        }
        
        return $this->redirectToRoute('app_quizz_end');
    }
}