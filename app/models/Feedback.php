<?php
/**
 * SIGAP-POLDA: Feedback Model (Survei Kepuasan Masyarakat / SKM)
 */

namespace App\Models;

use App\Core\Model;

class Feedback extends Model
{
    public function saveFeedback(int $reportId, int $ratingStars, int $speedScore, int $fairnessScore, string $comments): bool
    {
        return $this->execute(
            "INSERT INTO report_feedbacks (report_id, rating_stars, speed_score, fairness_score, comments)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                rating_stars = VALUES(rating_stars),
                speed_score = VALUES(speed_score),
                fairness_score = VALUES(fairness_score),
                comments = VALUES(comments)",
            [$reportId, $ratingStars, $speedScore, $fairnessScore, $comments]
        );
    }

    public function getByReportId(int $reportId): ?array
    {
        return $this->fetch("SELECT * FROM report_feedbacks WHERE report_id = ? LIMIT 1", [$reportId]);
    }
}
