<?php

declare(strict_types=1);

namespace Modules\Analytics\Actions;

use Modules\Analytics\DTO\AnalyticsSummaryDTO;

final readonly class ExportAnalyticsCsvAction
{
    public function execute(AnalyticsSummaryDTO $summary): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");

        $writeRow = static function (array $row) use ($handle): void {
            fputcsv($handle, $row, ';');
        };

        $writeRow(['Сводный аналитический отчет', config('app.clinic_name', 'DentalCRM')]);
        $writeRow(['Период', $summary->period->from->format('d.m.Y') . ' — ' . $summary->period->to->format('d.m.Y')]);
        $writeRow([]);

        $writeRow(['КЛЮЧЕВЫЕ ПОКАЗАТЕЛИ (KPI)', 'Значение']);
        $writeRow(['Новые пациенты', (string) $summary->newPatientsCount]);
        $writeRow(['Новые сделки', (string) $summary->newDealsCount]);
        $writeRow(['Сделки в работе / завершены', (string) $summary->convertedDealsCount]);
        $writeRow(['Конверсия в сделку (%)', number_format($summary->conversionRate, 1, ',', '') . '%']);
        $writeRow(['Выручка за период (₽)', number_format($summary->revenue, 2, ',', ' ')]);
        $writeRow(['Количество платежей', (string) $summary->paymentsCount]);
        $writeRow(['Текущая дебиторская задолженность (₽)', number_format($summary->totalReceivables, 2, ',', ' ')]);
        $writeRow(['Просроченные рассрочки (шт.)', (string) $summary->overdueDealsCount]);
        $writeRow(['Проведенные визиты', (string) $summary->completedVisitsCount]);
        $writeRow(['Неявки (no-show)', (string) $summary->noShowVisitsCount]);
        $writeRow(['Процент неявок (%)', number_format($summary->noShowRate, 1, ',', '') . '%']);
        $writeRow([]);

        $writeRow(['ВОРОНКА СДЕЛОК', 'Количество', 'Сумма (₽)', 'Доля (%)']);
        foreach ($summary->funnel as $row) {
            $writeRow([
                $row['label'],
                (string) $row['count'],
                number_format($row['amount'], 2, ',', ' '),
                number_format($row['share'], 1, ',', '') . '%',
            ]);
        }
        $writeRow([]);

        $writeRow(['КАНАЛЫ ПРИВЛЕЧЕНИЯ', 'Пациентов', 'Доля (%)']);
        foreach ($summary->sources as $row) {
            $writeRow([
                $row['label'],
                (string) $row['count'],
                number_format($row['share'], 1, ',', '') . '%',
            ]);
        }
        $writeRow([]);

        $writeRow(['ПРИЧИНЫ ОТКАЗОВ', 'Сделок', 'Доля (%)']);
        if ($summary->lossReasons === []) {
            $writeRow(['Отказов за период нет', '0', '0%']);
        } else {
            foreach ($summary->lossReasons as $row) {
                $writeRow([
                    $row['label'],
                    (string) $row['count'],
                    number_format($row['share'], 1, ',', '') . '%',
                ]);
            }
        }
        $writeRow([]);

        $writeRow(['ПОКАЗАТЕЛИ ПО ВРАЧАМ', 'Специализация', 'Проведено визитов', 'Неявок', 'Сделок', 'Сумма сделок (₽)']);
        foreach ($summary->doctors as $row) {
            $writeRow([
                $row['doctor_name'],
                $row['specialization'] ?? '—',
                (string) $row['completed_visits'],
                (string) $row['no_show_visits'],
                (string) $row['deals_count'],
                number_format($row['deals_amount'], 2, ',', ' '),
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
