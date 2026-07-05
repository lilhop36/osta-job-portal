<?php
declare(strict_types=1);

namespace App\Helpers;

class Pagination
{
    private int $currentPage;
    private int $perPage;
    private int $totalItems;
    private int $totalPages;

    public function __construct(int $currentPage = 1, int $perPage = 12, int $totalItems = 0)
    {
        $this->perPage = max(1, $perPage);
        $this->totalItems = max(0, $totalItems);
        $this->totalPages = max(1, (int) ceil($this->totalItems / $this->perPage));
        $this->currentPage = max(1, min($currentPage, $this->totalPages));
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    public function getPages(): array
    {
        $pages = [];
        $current = $this->currentPage;
        $total = $this->totalPages;

        if ($total <= 7) {
            for ($i = 1; $i <= $total; $i++) {
                $pages[] = $i;
            }
            return $pages;
        }

        $pages[] = 1;
        if ($current > 3) $pages[] = '...';

        $start = max(2, $current - 1);
        $end = min($total - 1, $current + 1);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($current < $total - 2) $pages[] = '...';
        $pages[] = $total;

        return $pages;
    }

    public function render(string $baseUrl, array $params = []): string
    {
        if ($this->totalPages <= 1) return '';

        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous
        if ($this->hasPrev()) {
            $params['page'] = $this->currentPage - 1;
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '"><i class="fas fa-chevron-left"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>';
        }

        // Page numbers
        foreach ($this->getPages() as $page) {
            if ($page === '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            } else {
                $isActive = $page === $this->currentPage ? ' active' : '';
                $params['page'] = $page;
                $html .= '<li class="page-item' . $isActive . '"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">' . $page . '</a></li>';
            }
        }

        // Next
        if ($this->hasNext()) {
            $params['page'] = $this->currentPage + 1;
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '"><i class="fas fa-chevron-right"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>';
        }

        $html .= '</ul></nav>';

        // Showing X of Y
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->perPage, $this->totalItems);
        $html .= '<p class="text-center text-muted mt-2">Showing ' . $start . '-' . $end . ' of ' . $this->totalItems . ' results</p>';

        return $html;
    }
}
