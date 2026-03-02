<?php

namespace Nomanurrahman\Services;

/**
 * A simple port of t-SNE algorithm to PHP.
 * Based on Karpathy's tsne.js
 */
class TSNE
{
    protected float $perplexity = 30.0;
    protected float $eta = 100.0;
    protected array $P = [];
    protected array $Y = [];
    protected array $gains = [];
    protected array $ystep = [];
    protected int $iter = 0;

    public function __construct(array $options = [])
    {
        $this->perplexity = $options['perplexity'] ?? 30.0;
        $this->eta = $options['eta'] ?? 100.0;
    }

    public function run(array $X, int $iterations = 500): array
    {
        $N = count($X);
        if ($N === 0) return [];
        
        $this->init($X);

        for ($i = 0; $i < $iterations; $i++) {
            $this->step();
        }

        return $this->Y;
    }

    protected function init(array $X): void
    {
        $N = count($X);
        $this->P = $this->computeP($X);
        
        $this->Y = [];
        $this->gains = [];
        $this->ystep = [];
        
        for ($i = 0; $i < $N; $i++) {
            $this->Y[$i] = [
                $this->gaussRandom() * 0.0001,
                $this->gaussRandom() * 0.0001
            ];
            $this->gains[$i] = [1.0, 1.0];
            $this->ystep[$i] = [0.0, 0.0];
        }
        
        $this->iter = 0;
    }

    protected function computeP(array $X): array
    {
        $N = count($X);
        $P = array_fill(0, $N, array_fill(0, $N, 0.0));
        $target_entropy = log($this->perplexity);

        for ($i = 0; $i < $N; $i++) {
            $beta = 1.0;
            $betamin = -INF;
            $betamax = INF;
            $tol = 1e-4;

            // Binary search for beta
            for ($k = 0; $k < 100; $k++) {
                $sum_pi = 0.0;
                for ($j = 0; $j < $N; $j++) {
                    if ($i === $j) continue;
                    $dist = $this->L2($X[$i], $X[$j]);
                    $p = exp(-$dist * $beta);
                    $P[$i][$j] = $p;
                    $sum_pi += $p;
                }

                $entropy = 0.0;
                for ($j = 0; $j < $N; $j++) {
                    if ($i === $j) continue;
                    $P[$i][$j] /= ($sum_pi + 1e-100);
                    $entropy += $P[$i][$j] * log($P[$i][$j] + 1e-100);
                }
                $entropy = -$entropy;

                if (abs($entropy - $target_entropy) < $tol) break;

                if ($entropy > $target_entropy) {
                    $betamin = $beta;
                    $beta = ($betamax === INF) ? ($beta * 2) : (($beta + $betamax) / 2);
                } else {
                    $betamax = $beta;
                    $beta = ($betamin === -INF) ? ($beta / 2) : (($beta + $betamin) / 2);
                }
            }
        }

        // Symmetrize
        $Pout = array_fill(0, $N, array_fill(0, $N, 0.0));
        for ($i = 0; $i < $N; $i++) {
            for ($j = 0; $j < $N; $j++) {
                $val = ($P[$i][$j] + $P[$j][$i]) / (2 * $N);
                $Pout[$i][$j] = max($val, 1e-100);
            }
        }

        return $Pout;
    }

    protected function step(): void
    {
        $this->iter++;
        $N = count($this->Y);

        $grad = array_fill(0, $N, [0.0, 0.0]);
        $quals = array_fill(0, $N, array_fill(0, $N, 0.0));
        $sum_q = 0.0;

        for ($i = 0; $i < $N; $i++) {
            for ($j = 0; $j < $N; $j++) {
                if ($i === $j) continue;
                $dist = $this->L2($this->Y[$i], $this->Y[$j]);
                $q = 1.0 / (1.0 + $dist);
                $quals[$i][$j] = $q;
                $sum_q += $q;
            }
        }

        for ($i = 0; $i < $N; $i++) {
            for ($j = 0; $j < $N; $j++) {
                if ($i === $j) continue;
                $q = $quals[$i][$j] / $sum_q;
                $mult = ($this->P[$i][$j] - $q) * $quals[$i][$j];
                $grad[$i][0] += $mult * ($this->Y[$i][0] - $this->Y[$j][0]);
                $grad[$i][1] += $mult * ($this->Y[$i][1] - $this->Y[$j][1]);
            }
        }

        $momentum = ($this->iter < 250) ? 0.5 : 0.8;
        for ($i = 0; $i < $N; $i++) {
            for ($d = 0; $d < 2; $d++) {
                $g = $this->gains[$i][$d];
                $s = $this->ystep[$i][$d];
                $gr = $grad[$i][$d];

                $newg = ($this->sign($gr) != $this->sign($s)) ? ($g + 0.2) : ($g * 0.8);
                $newg = max($newg, 0.01);
                $this->gains[$i][$d] = $newg;

                $news = $momentum * $s - $this->eta * $newg * $gr;
                $this->ystep[$i][$d] = $news;
                $this->Y[$i][$d] += $news;
            }
        }
    }

    protected function L2(array $x1, array $x2): float
    {
        $sum = 0.0;
        foreach ($x1 as $i => $v) {
            $sum += ($v - $x2[$i]) ** 2;
        }
        return $sum;
    }

    protected function gaussRandom(): float
    {
        static $return_v = false;
        static $v_val = 0.0;
        if ($return_v) {
            $return_v = false;
            return $v_val;
        }
        $u = 2 * mt_rand() / mt_getrandmax() - 1;
        $v = 2 * mt_rand() / mt_getrandmax() - 1;
        $r = $u * $u + $v * $v;
        if ($r == 0 || $r > 1) return $this->gaussRandom();
        $c = sqrt(-2 * log($r) / $r);
        $v_val = $v * $c;
        $return_v = true;
        return $u * $c;
    }

    protected function sign($x) {
        return $x > 0 ? 1 : ($x < 0 ? -1 : 0);
    }
}
