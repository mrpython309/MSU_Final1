@extends('layouts/contentNavbarLayout')

@section('title', 'Asset Dashboard')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/formValidation.min.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@endsection

@section('page-style')
<style>
    .asset-dashboard {
        --ad-violet: #7c3aed;
        --ad-indigo: #6366f1;
        --ad-blue: #3b82f6;
        --ad-cyan: #06b6d4;
        --ad-emerald: #10b981;
        --ad-amber: #f59e0b;
        --ad-orange: #f97316;
        --ad-rose: #f43f5e;
        --ad-pink: #ec4899;
        --ad-slate: #64748b;
        --ad-card-radius: 1rem;
        --ad-shadow: 0 4px 20px rgba(99, 102, 241, 0.12);
        --ad-shadow-hover: 0 12px 32px rgba(99, 102, 241, 0.22);
    }

    .asset-dashboard__hero {
        background: linear-gradient(120deg, #6366f1 0%, #8b5cf6 35%, #06b6d4 100%);
        border: none;
        border-radius: var(--ad-card-radius);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 40px rgba(99, 102, 241, 0.35);
        position: relative;
        overflow: hidden;
    }

    .asset-dashboard__hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .asset-dashboard__hero::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: 5%;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .asset-dashboard__hero-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        letter-spacing: -0.03em;
        color: #fff;
        position: relative;
        z-index: 1;
    }

    .asset-dashboard__hero-subtitle {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.82);
        margin-bottom: 0;
        position: relative;
        z-index: 1;
    }

    .asset-dashboard-filters {
        position: relative;
        z-index: 1;
    }

    .asset-dashboard-filters .form-label {
        font-size: 0.6875rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        color: rgba(255, 255, 255, 0.75);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .asset-dashboard-filters__row { row-gap: 0.75rem; }

    .asset-dashboard-filters__field {
        flex: 1 1 10rem;
        min-width: 10rem;
        max-width: 100%;
    }

    .asset-dashboard-filters__field .select2-container { width: 100% !important; }

    .asset-dashboard-filters .form-control-sm,
    .asset-dashboard-filters .form-select-sm {
        min-height: calc(1.5em + 0.5rem + 2px);
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.95);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .asset-dashboard-filters .form-control-sm:focus,
    .asset-dashboard-filters .form-select-sm:focus {
        border-color: #fff;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
    }

    .asset-dashboard-filters .select2-container--default .select2-selection--single {
        min-height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 0.625rem;
        font-size: 0.875rem;
        background: rgba(255, 255, 255, 0.95);
    }

    .asset-dashboard-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
    }

    .asset-dashboard-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
    }

    .asset-dashboard-filters__actions .btn-apply-vibrant {
        min-width: 5.5rem;
        border-radius: 0.625rem;
        font-weight: 600;
        border: none;
        color: #6366f1;
        background: #fff;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .asset-dashboard-filters__actions .btn-apply-vibrant:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        color: #4f46e5;
        background: #fff;
    }

    /* ── Vibrant stat cards ── */
    .asset-stat-card {
        border: none;
        border-radius: var(--ad-card-radius);
        box-shadow: var(--ad-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        position: relative;
        color: #fff;
    }

    .asset-stat-card--primary {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        box-shadow: 0 8px 28px rgba(99, 102, 241, 0.45);
    }
    .asset-stat-card--success {
        background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
        box-shadow: 0 8px 28px rgba(16, 185, 129, 0.4);
    }
    .asset-stat-card--warning {
        background: linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #fbbf24 100%);
        box-shadow: 0 8px 28px rgba(245, 158, 11, 0.4);
    }
    .asset-stat-card--danger {
        background: linear-gradient(135deg, #dc2626 0%, #f43f5e 50%, #fb7185 100%);
        box-shadow: 0 8px 28px rgba(244, 63, 94, 0.4);
    }

    .asset-stat-card:hover {
        transform: translateY(-8px) scale(1.02);
    }
    .asset-stat-card--primary:hover { box-shadow: 0 16px 40px rgba(99, 102, 241, 0.55); }
    .asset-stat-card--success:hover { box-shadow: 0 16px 40px rgba(16, 185, 129, 0.5); }
    .asset-stat-card--warning:hover { box-shadow: 0 16px 40px rgba(245, 158, 11, 0.5); }
    .asset-stat-card--danger:hover { box-shadow: 0 16px 40px rgba(244, 63, 94, 0.5); }

    .asset-stat-card::after {
        content: '';
        position: absolute;
        bottom: -2rem;
        right: -2rem;
        width: 8rem;
        height: 8rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        pointer-events: none;
    }

    .asset-stat-card .card-body {
        padding: 1.5rem 1.4rem;
        position: relative;
        z-index: 2;
    }

    .asset-stat-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1.1rem;
    }

    .asset-stat-card__icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .asset-stat-card__icon i {
        color: #fff !important;
        font-size: 1.5rem;
    }

    .asset-stat-card__chip {
        font-size: 0.6875rem;
        font-weight: 700;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.35);
    }

    .asset-stat-card__value {
        font-size: 2.25rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.04em;
        margin-bottom: 0.4rem;
        color: #fff;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .asset-stat-card__label {
        font-size: 0.8125rem;
        color: rgba(255, 255, 255, 0.88);
        margin-bottom: 0.85rem;
        font-weight: 500;
    }

    .asset-stat-card__track {
        height: 6px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);
        overflow: hidden;
    }

    .asset-stat-card__track-fill {
        height: 100%;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
    }

    /* ── Value summary ── */
    .asset-value-summary {
        border: none;
        border-radius: var(--ad-card-radius);
        box-shadow: var(--ad-shadow);
        overflow: hidden;
        background: #fff;
    }

    .asset-value-summary .card-body { padding: 1.5rem 1.35rem; }

    .asset-value-summary__title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: linear-gradient(90deg, #6366f1, #06b6d4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .asset-value-summary__title i {
        -webkit-text-fill-color: #6366f1;
        font-size: 1.35rem;
    }

    .asset-value-metric {
        flex: 1 1 12rem;
        min-width: 12rem;
        padding: 1.2rem 1.25rem;
        border-radius: 0.875rem;
        border: none;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        color: #fff;
    }

    .asset-value-metric--primary {
        background: linear-gradient(135deg, #6366f1, #818cf8);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
    }
    .asset-value-metric--info {
        background: linear-gradient(135deg, #0891b2, #22d3ee);
        box-shadow: 0 6px 20px rgba(6, 182, 212, 0.35);
    }
    .asset-value-metric--warning {
        background: linear-gradient(135deg, #d97706, #fbbf24);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
    }

    .asset-value-metric:hover {
        transform: translateY(-4px);
    }
    .asset-value-metric--primary:hover { box-shadow: 0 10px 28px rgba(99, 102, 241, 0.45); }
    .asset-value-metric--info:hover { box-shadow: 0 10px 28px rgba(6, 182, 212, 0.45); }
    .asset-value-metric--warning:hover { box-shadow: 0 10px 28px rgba(245, 158, 11, 0.45); }

    .asset-value-metric .avatar-initial {
        background: rgba(255, 255, 255, 0.22) !important;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .asset-value-metric .text-primary,
    .asset-value-metric .text-info,
    .asset-value-metric .text-warning { color: #fff !important; }

    .asset-value-metric__label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 0.35rem;
    }

    .asset-value-metric__amount {
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 0;
        letter-spacing: -0.02em;
        color: #fff !important;
    }

    .asset-depreciation-bar {
        height: 0.75rem;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(99,102,241,0.1), rgba(6,182,212,0.1));
        overflow: hidden;
    }

    .asset-depreciation-bar .progress-bar {
        border-radius: 999px;
        background: linear-gradient(90deg, #f59e0b, #f43f5e, #ec4899);
        font-size: 0;
        box-shadow: 0 0 12px rgba(244, 63, 94, 0.4);
    }

    .asset-depreciation-bar__caption {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.75rem;
        font-size: 0.8125rem;
        color: var(--bs-secondary-color);
    }

    .asset-depreciation-bar__caption strong {
        background: linear-gradient(90deg, #f59e0b, #f43f5e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
    }

    /* ── Panel cards ── */
    .asset-panel {
        border: none;
        border-radius: var(--ad-card-radius);
        box-shadow: var(--ad-shadow);
        height: 100%;
        overflow: hidden;
        transition: box-shadow 0.25s ease, transform 0.25s ease;
        background: #fff;
    }

    .asset-panel:hover {
        box-shadow: var(--ad-shadow-hover);
        transform: translateY(-3px);
    }

    .asset-panel .card-header {
        border-bottom: none;
        padding: 1.15rem 1.35rem;
        position: relative;
    }

    .asset-panel--types .card-header { background: linear-gradient(90deg, rgba(99,102,241,0.12), rgba(139,92,246,0.06)); border-left: 4px solid #6366f1; }
    .asset-panel--assign .card-header { background: linear-gradient(90deg, rgba(6,182,212,0.12), rgba(34,211,238,0.06)); border-left: 4px solid #06b6d4; }
    .asset-panel--maint .card-header { background: linear-gradient(90deg, rgba(245,158,11,0.14), rgba(251,191,36,0.06)); border-left: 4px solid #f59e0b; }
    .asset-panel--warranty .card-header { background: linear-gradient(90deg, rgba(244,63,94,0.12), rgba(251,113,133,0.06)); border-left: 4px solid #f43f5e; }

    .asset-panel__header-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.625rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #fff;
    }

    .asset-panel--types .asset-panel__header-icon { background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
    .asset-panel--assign .asset-panel__header-icon { background: linear-gradient(135deg, #0891b2, #22d3ee); box-shadow: 0 4px 12px rgba(6,182,212,0.4); }
    .asset-panel--maint .asset-panel__header-icon { background: linear-gradient(135deg, #d97706, #fbbf24); box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
    .asset-panel--warranty .asset-panel__header-icon { background: linear-gradient(135deg, #e11d48, #fb7185); box-shadow: 0 4px 12px rgba(244,63,94,0.4); }

    .asset-panel .card-title {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.3;
    }

    .asset-panel__subtitle {
        display: block;
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
        margin-top: 0.15rem;
        font-weight: 500;
    }

    .asset-panel .card-body {
        padding: 1rem 1.25rem 1.25rem;
        max-height: 420px;
        overflow-y: auto;
    }

    .asset-panel .card-body::-webkit-scrollbar { width: 5px; }
    .asset-panel .card-body::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: rgba(99, 102, 241, 0.25);
    }

    .asset-panel .badge.bg-label-primary,
    .asset-panel__count-badge {
        background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
        color: #fff !important;
        font-weight: 600;
        padding: 0.35rem 0.75rem;
        font-size: 0.6875rem;
    }

    .asset-panel--assign .asset-panel__count-badge { background: linear-gradient(135deg, #0891b2, #22d3ee) !important; }
    .asset-panel--maint .asset-panel__count-badge { background: linear-gradient(135deg, #d97706, #fbbf24) !important; color: #fff !important; }
    .asset-panel--warranty .asset-panel__count-badge { background: linear-gradient(135deg, #e11d48, #fb7185) !important; }

    .asset-panel__header-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .asset-panel__header-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ── Asset By Types ── */
    .asset-type-list { display: flex; flex-direction: column; gap: 0.65rem; }

    .asset-type-card {
        list-style: none;
        display: flex;
        align-items: stretch;
        gap: 0.75rem;
        padding: 0.85rem 0.95rem;
        border-radius: 0.875rem;
        background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(139,92,246,0.03) 100%);
        border: 1px solid rgba(99, 102, 241, 0.12);
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .asset-type-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(99, 102, 241, 0.15);
        border-color: rgba(99, 102, 241, 0.28);
    }

    .asset-type-card__rank {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 800;
        color: #6366f1;
        background: rgba(99, 102, 241, 0.15);
        align-self: center;
    }

    .asset-type-card__rank--top {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.35);
    }

    .asset-type-card__body { flex: 1; min-width: 0; }

    .asset-type-card__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.55rem;
    }

    .asset-type-card__name-wrap {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-width: 0;
    }

    .asset-type-card__icon {
        width: 2.15rem;
        height: 2.15rem;
        border-radius: 0.55rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    .asset-type-card__name {
        font-size: 0.875rem;
        font-weight: 700;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .asset-type-card__stats {
        text-align: right;
        flex-shrink: 0;
    }

    .asset-type-card__count {
        display: block;
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.1;
        color: #6366f1;
    }

    .asset-type-card__pct {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
    }

    .asset-type-item__bar {
        height: 7px;
        border-radius: 999px;
        background: rgba(99, 102, 241, 0.12);
        overflow: hidden;
    }

    .asset-type-item__bar-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .asset-type-item__bar-fill.bg-primary { background: linear-gradient(90deg, #6366f1, #8b5cf6); box-shadow: 0 0 10px rgba(99,102,241,0.5); }
    .asset-type-item__bar-fill.bg-success { background: linear-gradient(90deg, #059669, #34d399); box-shadow: 0 0 10px rgba(16,185,129,0.4); }
    .asset-type-item__bar-fill.bg-info { background: linear-gradient(90deg, #0891b2, #22d3ee); box-shadow: 0 0 10px rgba(6,182,212,0.4); }
    .asset-type-item__bar-fill.bg-warning { background: linear-gradient(90deg, #d97706, #fbbf24); box-shadow: 0 0 10px rgba(245,158,11,0.4); }
    .asset-type-item__bar-fill.bg-danger { background: linear-gradient(90deg, #dc2626, #fb7185); box-shadow: 0 0 10px rgba(244,63,94,0.4); }
    .asset-type-item__bar-fill.bg-secondary { background: linear-gradient(90deg, #64748b, #94a3b8); }
    .asset-type-item__bar-fill.bg-dark { background: linear-gradient(90deg, #1e293b, #475569); }

    /* ── Feed list (assignments, maintenance, warranties) ── */
    .asset-feed-list { display: flex; flex-direction: column; gap: 0.7rem; }

    .asset-feed-item {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.9rem 1rem;
        border-radius: 0.875rem;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .asset-feed-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 4px 0 0 4px;
    }

    .asset-panel--assign .asset-feed-item::before { background: linear-gradient(180deg, #06b6d4, #0891b2); }
    .asset-panel--maint .asset-feed-item::before { background: linear-gradient(180deg, #fbbf24, #d97706); }
    .asset-panel--warranty .asset-feed-item::before { background: linear-gradient(180deg, #fb7185, #e11d48); }

    .asset-panel--assign .asset-feed-item:hover {
        border-color: rgba(6, 182, 212, 0.35);
        box-shadow: 0 8px 24px rgba(6, 182, 212, 0.18);
        transform: translateX(4px);
    }
    .asset-panel--maint .asset-feed-item:hover {
        border-color: rgba(245, 158, 11, 0.35);
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.18);
        transform: translateX(4px);
    }
    .asset-panel--warranty .asset-feed-item:hover {
        border-color: rgba(244, 63, 94, 0.35);
        box-shadow: 0 8px 24px rgba(244, 63, 94, 0.18);
        transform: translateX(4px);
    }

    .asset-feed-item__avatar {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.75rem;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: #fff;
    }

    .asset-panel--assign .asset-feed-item__avatar { background: linear-gradient(135deg, #0891b2, #22d3ee); box-shadow: 0 4px 12px rgba(6,182,212,0.35); }
    .asset-panel--maint .asset-feed-item__avatar { background: linear-gradient(135deg, #d97706, #fbbf24); box-shadow: 0 4px 12px rgba(245,158,11,0.35); }
    .asset-panel--warranty .asset-feed-item__avatar { background: linear-gradient(135deg, #e11d48, #fb7185); box-shadow: 0 4px 12px rgba(244,63,94,0.35); }

    .asset-feed-item__content { flex: 1; min-width: 0; }

    .asset-feed-item__title {
        font-size: 0.9rem;
        font-weight: 700;
        margin: 0 0 0.35rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .asset-feed-item__tags {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
    }

    .asset-feed-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        line-height: 1.3;
    }

    .asset-feed-tag i { font-size: 0.75rem; }

    .asset-panel--assign .asset-feed-tag--user { background: rgba(6,182,212,0.12); color: #0891b2; }
    .asset-panel--assign .asset-feed-tag--date { background: rgba(99,102,241,0.1); color: #6366f1; }

    .asset-feed-tag--pending { background: rgba(245,158,11,0.15); color: #b45309; }
    .asset-feed-tag--progress { background: rgba(6,182,212,0.15); color: #0891b2; }
    .asset-feed-tag--done { background: rgba(16,185,129,0.15); color: #059669; }
    .asset-feed-tag--cancel { background: rgba(244,63,94,0.12); color: #e11d48; }
    .asset-feed-tag--neutral { background: rgba(100,116,139,0.12); color: #64748b; }

    .asset-panel--maint .asset-feed-tag--date { background: rgba(245,158,11,0.12); color: #b45309; }

    .asset-panel--warranty .asset-feed-tag--parts { background: rgba(99,102,241,0.1); color: #6366f1; }
    .asset-panel--warranty .asset-feed-tag--expire { background: rgba(244,63,94,0.12); color: #e11d48; }
    .asset-panel--warranty .asset-feed-tag--urgent {
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: #fff;
        animation: asset-pulse 2s ease-in-out infinite;
    }

    @keyframes asset-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
    }

    .asset-feed-item__aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.4rem;
        flex-shrink: 0;
    }

    .asset-feed-item__action {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.625rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
    }

    .asset-panel--assign .asset-feed-item__action { background: rgba(6,182,212,0.12); color: #0891b2; }
    .asset-panel--maint .asset-feed-item__action { background: rgba(245,158,11,0.12); color: #d97706; }
    .asset-panel--warranty .asset-feed-item__action { background: rgba(244,63,94,0.12); color: #e11d48; }

    .asset-panel--assign .asset-feed-item:hover .asset-feed-item__action { background: linear-gradient(135deg, #0891b2, #22d3ee); color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,0.4); }
    .asset-panel--maint .asset-feed-item:hover .asset-feed-item__action { background: linear-gradient(135deg, #d97706, #fbbf24); color: #fff; box-shadow: 0 4px 12px rgba(245,158,11,0.4); }
    .asset-panel--warranty .asset-feed-item:hover .asset-feed-item__action { background: linear-gradient(135deg, #e11d48, #fb7185); color: #fff; box-shadow: 0 4px 12px rgba(244,63,94,0.4); }

    .asset-empty-state {
        text-align: center;
        padding: 2.5rem 1.25rem;
        border-radius: 0.875rem;
        list-style: none;
    }

    .asset-panel--types .asset-empty-state { background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.04)); border: 1px dashed rgba(99,102,241,0.25); }
    .asset-panel--assign .asset-empty-state { background: linear-gradient(135deg, rgba(6,182,212,0.08), rgba(34,211,238,0.04)); border: 1px dashed rgba(6,182,212,0.25); }
    .asset-panel--maint .asset-empty-state { background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(251,191,36,0.04)); border: 1px dashed rgba(245,158,11,0.3); }
    .asset-panel--warranty .asset-empty-state { background: linear-gradient(135deg, rgba(244,63,94,0.08), rgba(251,113,133,0.04)); border: 1px dashed rgba(244,63,94,0.25); }

    .asset-empty-state__icon {
        width: 3.5rem;
        height: 3.5rem;
        margin: 0 auto 0.75rem;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #fff;
    }

    .asset-panel--types .asset-empty-state__icon { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    .asset-panel--assign .asset-empty-state__icon { background: linear-gradient(135deg, #0891b2, #22d3ee); }
    .asset-panel--maint .asset-empty-state__icon { background: linear-gradient(135deg, #d97706, #fbbf24); }
    .asset-panel--warranty .asset-empty-state__icon { background: linear-gradient(135deg, #e11d48, #fb7185); }

    .asset-empty-state__title {
        font-size: 0.9375rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--bs-body-color);
    }

    .asset-empty-state__text {
        font-size: 0.8125rem;
        color: var(--bs-secondary-color);
        margin: 0;
    }

    /* ── Chart card ── */
    .asset-chart-card {
        border: none;
        border-radius: var(--ad-card-radius);
        overflow: hidden;
        background: #fff;
        box-shadow: 0 8px 32px rgba(99, 102, 241, 0.15);
    }

    .asset-chart-card .card-header {
        background: linear-gradient(120deg, #6366f1 0%, #8b5cf6 50%, #06b6d4 100%);
        border-bottom: none;
        padding: 1.25rem 1.5rem;
        color: #fff;
    }

    .asset-chart-card .card-header .card-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .asset-chart-card .card-header small {
        color: rgba(255, 255, 255, 0.78) !important;
    }

    .asset-chart-card .card-header .asset-panel__header-icon {
        background: rgba(255, 255, 255, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.35);
        box-shadow: none;
        color: #fff;
    }

    .asset-chart-card .card-body { padding: 1.5rem 1.5rem 1.75rem; }

    .asset-chart-layout {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1.5rem 2rem;
    }

    .asset-chart-wrap {
        position: relative;
        flex: 1 1 240px;
        max-width: 300px;
        min-height: 260px;
        margin: 0 auto;
        padding: 1rem;
        background: radial-gradient(circle, rgba(99,102,241,0.06) 0%, transparent 70%);
        border-radius: 50%;
    }

    .asset-chart-wrap canvas {
        filter: drop-shadow(0 10px 24px rgba(99, 102, 241, 0.2));
    }

    .asset-chart-legend {
        flex: 1 1 260px;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .asset-chart-legend__item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        transition: all 0.25s ease;
        cursor: default;
        background: var(--legend-bg, rgba(99,102,241,0.06));
        border-color: var(--legend-border, rgba(99,102,241,0.12));
    }

    .asset-chart-legend__item:hover {
        transform: translateX(5px) scale(1.01);
        box-shadow: 0 4px 16px var(--legend-shadow, rgba(99,102,241,0.2));
    }

    .asset-chart-legend__item.is-active {
        transform: translateX(5px) scale(1.02);
        box-shadow: 0 6px 20px var(--legend-shadow, rgba(99,102,241,0.25));
        border-color: var(--legend-color, #6366f1);
    }

    .asset-chart-legend__dot {
        width: 0.875rem;
        height: 0.875rem;
        border-radius: 50%;
        flex-shrink: 0;
        background: var(--legend-color, #6366f1);
        box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--legend-color, #6366f1);
    }

    .asset-chart-legend__label {
        flex: 1;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .asset-chart-legend__stats { text-align: right; flex-shrink: 0; }

    .asset-chart-legend__count {
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.2;
        color: var(--legend-color, #6366f1);
    }

    .asset-chart-legend__pct {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
    }

    .asset-chart-footer {
        margin-top: 1.25rem;
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        text-align: center;
        font-size: 0.875rem;
        background: linear-gradient(90deg, rgba(99,102,241,0.08), rgba(6,182,212,0.08));
        color: var(--bs-secondary-color);
    }

    .asset-chart-footer strong {
        background: linear-gradient(90deg, #6366f1, #06b6d4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
    }

    .asset-chart-card--value .card-header {
        background: linear-gradient(120deg, #6366f1 0%, #8b5cf6 45%, #f59e0b 100%);
    }

    /* Compact Audit History card with light emerald header */
    .asset-chart-card--audit {
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: var(--ad-shadow);
        border: none;
    }

    .asset-chart-card--audit .card-header {
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.04)) !important;
        border-left: 4px solid #10b981 !important;
        border-bottom: 1px solid rgba(16, 185, 129, 0.08) !important;
        padding: 1.15rem 1.35rem;
    }

    .asset-chart-card--audit .card-header .card-title {
        color: #0f172a !important;
        font-weight: 700;
        font-size: 1.05rem;
        margin: 0;
    }

    .asset-chart-card--audit .card-header small {
        color: #64748b !important;
        font-weight: 500;
        font-size: 0.75rem;
        display: block;
        margin-top: 0.15rem;
    }

    .asset-chart-card--audit .card-header .asset-panel__header-icon {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35) !important;
        border: none !important;
        color: #fff !important;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.625rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .asset-chart-card--audit .card-body {
        padding: 1rem 1.25rem 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        flex: 1 1 auto;
    }

    .asset-chart-card--audit .asset-chart-layout {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }

    .asset-chart-card--audit .asset-chart-wrap {
        flex: 0 0 165px;
        max-width: 165px;
        min-height: 165px;
        max-height: 165px;
        margin: 0 auto;
        padding: 0.25rem;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
    }

    .asset-chart-card--audit .asset-chart-wrap canvas {
        filter: drop-shadow(0 8px 18px rgba(16, 185, 129, 0.18));
    }

    .asset-chart-card--audit .asset-chart-legend {
        flex: 1 1 auto;
        gap: 0.45rem;
        margin-top: 0.25rem;
    }

    .asset-chart-card--audit .asset-chart-legend__item {
        padding: 0.45rem 0.85rem;
        border-radius: 0.625rem;
    }

    .asset-chart-card--audit .asset-chart-legend__label {
        font-size: 0.8125rem;
    }

    .asset-chart-card--audit .asset-chart-legend__count {
        font-size: 0.95rem;
    }

    .asset-chart-card--audit .asset-chart-footer {
        margin-top: 0.6rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.8125rem;
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.08), rgba(6, 182, 212, 0.08));
        color: #475569;
    }

    .asset-chart-card--audit .asset-chart-footer strong {
        background: linear-gradient(90deg, #10b981, #06b6d4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
    }

    .asset-charts-row .col-lg-6 {
        display: flex;
    }

    .asset-charts-row .asset-chart-card {
        width: 100%;
    }

    @media (max-width: 991.98px) {
        .asset-chart-layout {
            flex-direction: column;
        }
        .asset-chart-wrap {
            max-width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .asset-dashboard__hero { padding: 1.15rem; }
        .asset-value-metric { flex: 1 1 100%; }
        .asset-stat-card__value { font-size: 1.85rem; }
    }
</style>
@endsection

@section('content')

<div class="asset-dashboard">

<div class="asset-dashboard__hero">
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
      <div>
            <h4 class="asset-dashboard__hero-title">Asset Dashboard</h4>
            <p class="asset-dashboard__hero-subtitle">Overview of inventory, value, assignments, and maintenance at a glance</p>
        </div>
        <form method="GET" action="{{ route('dashboard.asset') }}" class="asset-dashboard-filters flex-grow-1 flex-lg-grow-0">
            <div class="asset-dashboard-filters__row d-flex flex-wrap align-items-end gap-3">
                <div class="asset-dashboard-filters__field">
                    <label for="company_id" class="form-label">Company</label>
            <select id="company_id" name="company_id"
                            class="form-select form-select-sm @error('company_id') is-invalid @enderror">
                <option value="">All Companies</option>
                  @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ request('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

                <div class="asset-dashboard-filters__field">
                    <label for="sub_category_id" class="form-label">Category</label>
            <select id="sub_category_id" name="sub_category_id"
                            class="form-select form-select-sm @error('sub_category_id') is-invalid @enderror">
                <option value="">All Categories</option>
                @foreach($subCategories as $subCategory)
                    <option value="{{ $subCategory->id }}"
                        {{ (string) request('sub_category_id') === (string) $subCategory->id ? 'selected' : '' }}>
                        {{ $subCategory->category_name }}
                    </option>
                @endforeach
            </select>
        </div>

                <div class="asset-dashboard-filters__field">
                    <label for="branch_id" class="form-label">Branch</label>
            <select id="branch_id" name="branch_id"
                            class="form-select form-select-sm @error('branch_id') is-invalid @enderror">
                <option value="">All Branchs</option>
                 @foreach($branches as $branch)
                    <option value="{{ $branch->id }}"
                        {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
                    @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

                <div class="asset-dashboard-filters__actions d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-apply-vibrant">
                        <i class="icon-base bx bx-filter-alt me-1"></i> Apply
                    </button>
        </div>
    </div>
    </form>
  </div>
          </div>

   <div class="row g-4 mb-4">
  @php
      $statTotal = max(1, (int) ($dashboard['total_assets'] ?? 0));
      $statCards = [
          ['class' => 'primary', 'label' => 'Total Assets', 'value' => $dashboard['total_assets'] ?? 0, 'icon' => 'bxs-truck', 'pct' => 100],
          ['class' => 'success', 'label' => 'Available Assets', 'value' => $dashboard['total_active_assets'] ?? 0, 'icon' => 'bx-check-circle', 'pct' => round((($dashboard['total_active_assets'] ?? 0) / $statTotal) * 100)],
          ['class' => 'warning', 'label' => 'Assigned Assets', 'value' => $dashboard['total_assigned_assets'] ?? 0, 'icon' => 'bx-git-repo-forked', 'pct' => round((($dashboard['total_assigned_assets'] ?? 0) / $statTotal) * 100)],
          ['class' => 'danger', 'label' => 'Under Maintenance', 'value' => $dashboard['total_under_maintenance_assets'] ?? 0, 'icon' => 'bx-wrench', 'pct' => round((($dashboard['total_under_maintenance_assets'] ?? 0) / $statTotal) * 100)],
      ];
  @endphp
  @foreach ($statCards as $stat)
  <div class="col-lg-3 col-sm-6">
    <div class="card asset-stat-card asset-stat-card--{{ $stat['class'] }} h-100">
      <div class="card-body">
        <div class="asset-stat-card__top">
          <div class="asset-stat-card__icon">
            <i class="icon-base bx {{ $stat['icon'] }}"></i>
          </div>
          @if ($stat['class'] !== 'primary')
          <span class="asset-stat-card__chip">{{ $stat['pct'] }}%</span>
          @else
          <span class="asset-stat-card__chip">100%</span>
          @endif
        </div>
        <h4 class="asset-stat-card__value mb-0">{{ number_format($stat['value']) }}</h4>
        <p class="asset-stat-card__label">{{ $stat['label'] }}</p>
        <div class="asset-stat-card__track">
          <div class="asset-stat-card__track-fill" style="width: {{ min(100, $stat['pct']) }}%;"></div>
      </div>
    </div>
  </div>
          </div>
  @endforeach
        </div>

<div class="row g-4 mb-4">
    <div class="col-12">
<div class="card asset-value-summary">
      <div class="card-body">
      <h4 class="asset-value-summary__title">
        <i class="icon-base bx bx-wallet"></i>
        Asset Value Summary
      </h4>

      <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
        <div class="asset-value-metric asset-value-metric--primary d-flex align-items-center gap-3">
          <div class="avatar avatar-lg flex-shrink-0">
            <div class="avatar-initial bg-label-primary rounded">
              <div class="text-primary">
                <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g id="Laptop">
                    <path id="Vector" opacity="0.2" d="M5.9375 26.125V10.6875C5.9375 10.0576 6.18772 9.45352 6.63312 9.00812C7.07852 8.56272 7.68261 8.3125 8.3125 8.3125H29.6875C30.3174 8.3125 30.9215 8.56272 31.3669 9.00812C31.8123 9.45352 32.0625 10.0576 32.0625 10.6875V26.125H5.9375Z" fill="currentColor"></path>
                    <path id="Vector_2" d="M5.9375 26.125V10.6875C5.9375 10.0576 6.18772 9.45352 6.63312 9.00812C7.07852 8.56272 7.68261 8.3125 8.3125 8.3125H29.6875C30.3174 8.3125 30.9215 8.56272 31.3669 9.00812C31.8123 9.45352 32.0625 10.0576 32.0625 10.6875V26.125M21.375 13.0625H16.625M3.5625 26.125H34.4375V28.5C34.4375 29.1299 34.1873 29.734 33.7419 30.1794C33.2965 30.6248 32.6924 30.875 32.0625 30.875H5.9375C5.30761 30.875 4.70352 30.6248 4.25812 30.1794C3.81272 29.734 3.5625 29.1299 3.5625 28.5V26.125Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                  </g>
                </svg>
              </div>
            </div>
          </div>
          <div>
            <p class="asset-value-metric__label">Total Purchase Value</p>
            <h4 class="asset-value-metric__amount text-primary">{{ number_format($dashboard['total_purchase_value'],2) ?? 0}}</h4>
          </div>
        </div>
        <div class="asset-value-metric asset-value-metric--info d-flex align-items-center gap-3">
          <div class="avatar avatar-lg flex-shrink-0">
            <div class="avatar-initial bg-label-info rounded">
              <div class="text-info">
                <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g id="Lightbulb">
                    <path id="Vector" opacity="0.2" d="M11.6822 24.7891C10.2684 23.6898 9.12342 22.2832 8.33388 20.6759C7.54435 19.0685 7.13099 17.3025 7.12513 15.5117C7.09544 9.06954 12.2759 3.71095 18.7181 3.56251C21.2113 3.50341 23.6599 4.23078 25.7166 5.64147C27.7732 7.05217 29.3335 9.07457 30.1761 11.4219C31.0188 13.7691 31.101 16.3221 30.4112 18.7188C29.7214 21.1154 28.2945 23.2341 26.3329 24.7742C25.8996 25.1092 25.5486 25.5388 25.3068 26.0301C25.065 26.5215 24.9387 27.0617 24.9376 27.6094V28.5C24.9376 28.815 24.8125 29.117 24.5898 29.3397C24.3671 29.5624 24.0651 29.6875 23.7501 29.6875H14.2501C13.9352 29.6875 13.6331 29.5624 13.4104 29.3397C13.1877 29.117 13.0626 28.815 13.0626 28.5V27.6094C13.0589 27.0658 12.9329 26.5301 12.6939 26.0418C12.4549 25.5536 12.1091 25.1255 11.6822 24.7891Z" fill="currentColor"></path>
                    <path id="Union" fill-rule="evenodd" clip-rule="evenodd" d="M25.1509 6.46609C23.2675 5.17419 21.0251 4.50807 18.7418 4.5622L18.7411 4.56221C18.4983 4.56781 18.2574 4.58151 18.0187 4.60305L18.6951 2.56275C21.398 2.49881 24.0526 3.28743 26.2822 4.8168C28.512 6.34629 30.2037 8.53899 31.1173 11.0839C32.031 13.6289 32.1201 16.3969 31.3722 18.9954C30.6243 21.5938 29.0772 23.8909 26.9505 25.5607L26.9445 25.5654L26.9445 25.5654C26.6318 25.8071 26.3785 26.1171 26.204 26.4717C26.0295 26.8263 25.9384 27.2161 25.9376 27.6113V28.5C25.9376 29.0801 25.7072 29.6365 25.2969 30.0468C24.8867 30.457 24.3303 30.6875 23.7501 30.6875H14.2501C13.67 30.6875 13.1136 30.457 12.7033 30.0468C12.2931 29.6365 12.0626 29.0801 12.0626 28.5V27.6131C12.0595 27.2206 11.9683 26.8339 11.7957 26.4815C11.6232 26.1289 11.3737 25.8196 11.0656 25.5764L11.7414 23.5378C11.9208 23.6976 12.1057 23.8517 12.296 23.9996L11.6821 24.7891L12.301 24.0035C12.8459 24.4328 13.2871 24.9792 13.5921 25.6022C13.8971 26.2252 14.0579 26.9089 14.0626 27.6025L14.0627 27.6094L14.0626 28.5C14.0626 28.5497 14.0824 28.5974 14.1175 28.6326C14.1527 28.6677 14.2004 28.6875 14.2501 28.6875H23.7501C23.7999 28.6875 23.8475 28.6677 23.8827 28.6326C23.9179 28.5974 23.9376 28.5497 23.9376 28.5V27.6094L23.9376 27.6074C23.939 26.9073 24.1004 26.2167 24.4096 25.5885C24.7181 24.9616 25.1657 24.4133 25.7181 23.9855C27.5131 22.5752 28.8188 20.6359 29.4502 18.4422C30.082 16.2473 30.0067 13.9093 29.235 11.7597C28.4633 9.61009 27.0344 7.75799 25.1509 6.46609ZM11.7414 23.5378L11.7414 23.5378L18.0187 4.60305L18.018 4.6031L18.6944 2.56276C11.7043 2.72418 6.09331 8.53234 6.12513 15.5156C6.13159 17.458 6.57998 19.3733 7.43632 21.1167C8.29225 22.8593 9.53332 24.3843 11.0656 25.5764L11.7414 23.5378ZM11.7414 23.5378C10.7009 22.6109 9.84781 21.4898 9.23145 20.235C8.50882 18.7638 8.13049 17.1475 8.12512 15.5084L8.12512 15.5071C8.09905 9.84987 12.4637 5.10456 18.018 4.6031L11.7414 23.5378ZM12.0627 34.4375C12.0627 33.8852 12.5104 33.4375 13.0627 33.4375H24.9377C25.49 33.4375 25.9377 33.8852 25.9377 34.4375C25.9377 34.9898 25.49 35.4375 24.9377 35.4375H13.0627C12.5104 35.4375 12.0627 34.9898 12.0627 34.4375ZM20.3697 7.44532C19.8252 7.35302 19.3089 7.71961 19.2166 8.26412C19.1243 8.80864 19.4909 9.32489 20.0354 9.41719C21.2827 9.62862 22.4336 10.222 23.3292 11.1154C24.2249 12.0087 24.8212 13.1581 25.0358 14.4048C25.1295 14.9491 25.6467 15.3144 26.191 15.2207C26.7353 15.127 27.1005 14.6098 27.0068 14.0655C26.722 12.4107 25.9305 10.8851 24.7417 9.69934C23.5528 8.51353 22.0252 7.72596 20.3697 7.44532Z" fill="currentColor"></path>
                  </g>
                </svg>
              </div>
            </div>
          </div>
          <div>
            <p class="asset-value-metric__label">Total Current Value</p>
            <h4 class="asset-value-metric__amount text-info">{{ number_format($dashboard['total_current_value'],2) ?? 0}}</h4>
          </div>
        </div>
        <div class="asset-value-metric asset-value-metric--warning d-flex align-items-center gap-3">
          <div class="avatar avatar-lg flex-shrink-0">
            <div class="avatar-initial bg-label-warning rounded">
              <div class="text-warning">
                <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g id="Check">
                    <path id="Vector" opacity="0.2" d="M8.08984 29.9102C6.72422 28.5445 7.62969 25.6797 6.93203 24.0023C6.23438 22.325 3.5625 20.8555 3.5625 19C3.5625 17.1445 6.20469 15.7344 6.93203 13.9977C7.65938 12.2609 6.72422 9.45547 8.08984 8.08984C9.45547 6.72422 12.3203 7.62969 13.9977 6.93203C15.675 6.23438 17.1445 3.5625 19 3.5625C20.8555 3.5625 22.2656 6.20469 24.0023 6.93203C25.7391 7.65938 28.5445 6.72422 29.9102 8.08984C31.2758 9.45547 30.3703 12.3203 31.068 13.9977C31.7656 15.675 34.4375 17.1445 34.4375 19C34.4375 20.8555 31.7953 22.2656 31.068 24.0023C30.3406 25.7391 31.2758 28.5445 29.9102 29.9102C28.5445 31.2758 25.6797 30.3703 24.0023 31.068C22.325 31.7656 20.8555 34.4375 19 34.4375C17.1445 34.4375 15.7344 31.7953 13.9977 31.068C12.2609 30.3406 9.45547 31.2758 8.08984 29.9102Z" fill="currentColor"></path>
                    <path id="Vector_2" d="M25.5312 15.4375L16.818 23.75L12.4687 19.5937M8.08984 29.9102C6.72422 28.5445 7.62969 25.6797 6.93203 24.0023C6.23437 22.325 3.5625 20.8555 3.5625 19C3.5625 17.1445 6.20469 15.7344 6.93203 13.9977C7.65937 12.2609 6.72422 9.45547 8.08984 8.08984C9.45547 6.72422 12.3203 7.62969 13.9977 6.93203C15.675 6.23437 17.1445 3.5625 19 3.5625C20.8555 3.5625 22.2656 6.20469 24.0023 6.93203C25.7391 7.65937 28.5445 6.72422 29.9102 8.08984C31.2758 9.45547 30.3703 12.3203 31.068 13.9977C31.7656 15.675 34.4375 17.1445 34.4375 19C34.4375 20.8555 31.7953 22.2656 31.068 24.0023C30.3406 25.7391 31.2758 28.5445 29.9102 29.9102C28.5445 31.2758 25.6797 30.3703 24.0023 31.068C22.325 31.7656 20.8555 34.4375 19 34.4375C17.1445 34.4375 15.7344 31.7953 13.9977 31.068C12.2609 30.3406 9.45547 31.2758 8.08984 29.9102Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                  </g>
                </svg>
              </div>
            </div>
          </div>
          <div>
            <p class="asset-value-metric__label">Total Depreciation</p>
            <h4 class="asset-value-metric__amount text-warning">{{ number_format($dashboard['total_depreciation'],2) ?? 0}}</h4>
        </div>
      </div>
    </div>

      <div class="progress asset-depreciation-bar">
        <div class="progress-bar" role="progressbar" style="width: {{ $dashboard['depreciation_percentage'] ?? 0 }}%;" aria-valuenow="{{ $dashboard['depreciation_percentage'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
      <div class="asset-depreciation-bar__caption">
        <span>Depreciation rate</span>
        <strong>{{ $dashboard['depreciation_percentage'] ?? 0 }}%</strong>
            </div>
  </div>
</div>

    </div>
</div>



<div class="row g-4">

<div class="col-md-6 order-0 mb-4">
        <div class="card asset-chart-card asset-chart-card--audit h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="card-title m-0">Audit History</h5>
            <small>Status breakdown across your portfolio</small>
        </div>
        <span class="asset-panel__header-icon">
            <i class="icon-base bx bx-check-shield"></i>
        </span>
      </div>
      <div class="card-body">

    <div class="asset-chart-layout">
        <div class="asset-chart-wrap">
            <canvas id="assetAuditChart"></canvas>
        </div>

        <div class="asset-chart-legend" id="assetAuditChartLegend">
            @php
                $auditChartTotal = max(1, (int) ($dashboard['total_assets'] ?? 0));
                $auditVibrantColors = ['#10b981', '#f43f5e', '#f59e0b'];
            @endphp
            @foreach ($assetAuditChartSeries ?? [] as $series)
            @php
                $seriesValue = (int) ($series['value'] ?? 0);
                $seriesPct = round(($seriesValue / $auditChartTotal) * 100, 1);
                $vColor = $series['color'] ?? $auditVibrantColors[$loop->index % count($auditVibrantColors)];
            @endphp
            <div class="asset-chart-legend__item"
                 data-chart-index="{{ $loop->index }}"
                 data-chart-target="audit"
                 style="--legend-color: {{ $vColor }}; --legend-bg: {{ $vColor }}14; --legend-border: {{ $vColor }}33; --legend-shadow: {{ $vColor }}40;">
                <span class="asset-chart-legend__dot"></span>
                <span class="asset-chart-legend__label">{{ $series['label'] }}</span>
                <div class="asset-chart-legend__stats">
                    <div class="asset-chart-legend__count">{{ number_format($seriesValue) }}</div>
                    <div class="asset-chart-legend__pct">{{ $seriesPct }}%</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="asset-chart-footer">
        Tracking <strong>{{ number_format($dashboard['total_assets'] ?? 0) }}</strong> assets in total
    </div>

      </div>
     </div>
  </div>


  <div class="col-md-6 order-0 mb-4" >

    <div class="card asset-panel asset-panel--assign h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="asset-panel__header-left">
          <span class="asset-panel__header-icon"><i class="icon-base bx bx-transfer-alt"></i></span>
          <div>
            <h5 class="card-title">Recent Assignments</h5>
            <small class="asset-panel__subtitle">Latest asset allocation activity</small>
          </div>
        </div>
        <span class="badge asset-panel__count-badge rounded-pill">{{ count($dashboard['recent_assignments'] ?? []) }}</span>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0 asset-feed-list">

            @forelse ($dashboard['recent_assignments'] as $assignment)  

            <li class="asset-feed-item">
              <div class="asset-feed-item__avatar">
                <i class="icon-base bx bx-package"></i>
              </div>
              <div class="asset-feed-item__content">
                <h6 class="asset-feed-item__title">{{ $assignment->asset->asset_name ?? '--' }}</h6>
                <div class="asset-feed-item__tags">
                  <span class="asset-feed-tag asset-feed-tag--user">
                    <i class="icon-base bx bx-user"></i>
                    {{ $assignment->assigned_to === 'branch'
        ? optional($assignment->toBranch)->name ?? 'N/A'
                        : optional($assignment->employee)->name ?? 'N/A' }}
                  </span>
                  <span class="asset-feed-tag asset-feed-tag--date">
                    <i class="icon-base bx bx-calendar"></i>
                    {{ $assignment->assign_date?->format('d M Y') }}
                  </span>
                </div>
              </div>
              <div class="asset-feed-item__aside">
                <a href="{{ route('asset.show', $assignment->asset_id) }}" class="asset-feed-item__action" title="View asset">
                  <i class="icon-base bx bx-chevron-right icon-20px scaleX-n1-rtl"></i>
                </a>
            </div>
          </li>

            @empty

            <li class="asset-empty-state">
                <div class="asset-empty-state__icon"><i class="icon-base bx bx-transfer"></i></div>
                <div class="asset-empty-state__title">No recent assignments</div>
                <p class="asset-empty-state__text">New assignments will show up here</p>
            </li>

            @endforelse

        </ul>
      </div>
    </div>

  </div>


  <div class="col-md-6 order-0 mb-4" >

    <div class="card asset-panel asset-panel--maint h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="asset-panel__header-left">
          <span class="asset-panel__header-icon"><i class="icon-base bx bx-wrench"></i></span>
          <div>
            <h5 class="card-title">Recent Maintenance</h5>
            <small class="asset-panel__subtitle">Ongoing and recent repair records</small>
          </div>
        </div>
        <span class="badge asset-panel__count-badge rounded-pill">{{ count($dashboard['recent_maintenances'] ?? []) }}</span>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0 asset-feed-list">

          @forelse ($dashboard['recent_maintenances'] as $recentMaintenance)
                  @php
                    $maintenanceStatus = strtolower((string) $recentMaintenance->status);
              $statusTagClass = match ($maintenanceStatus) {
                  'pending' => 'asset-feed-tag--pending',
                  'in_progress' => 'asset-feed-tag--progress',
                  'completed' => 'asset-feed-tag--done',
                  'cancelled' => 'asset-feed-tag--cancel',
                  default => 'asset-feed-tag--neutral',
              };
              $statusIcon = match ($maintenanceStatus) {
                  'pending' => 'bx-time-five',
                  'in_progress' => 'bx-loader-circle',
                  'completed' => 'bx-check-circle',
                  'cancelled' => 'bx-x-circle',
                  default => 'bx-info-circle',
              };
                  @endphp
            <li class="asset-feed-item">
              <div class="asset-feed-item__avatar">
                <i class="icon-base bx bx-cog"></i>
                </div>
              <div class="asset-feed-item__content">
                <h6 class="asset-feed-item__title">{{ $recentMaintenance->asset->asset_name ?? '--' }}</h6>
                <div class="asset-feed-item__tags">
                  <span class="asset-feed-tag {{ $statusTagClass }}">
                    <i class="icon-base bx {{ $statusIcon }}"></i>
                    {{ ucfirst(str_replace('_', ' ', $recentMaintenance->status)) }}
                  </span>
                  <span class="asset-feed-tag asset-feed-tag--date">
                    <i class="icon-base bx bx-calendar"></i>
                    {{ $recentMaintenance->maintenance_date?->format('d M Y') }}
                  </span>
              </div>
              </div>
              <div class="asset-feed-item__aside">
                <a href="{{ route('asset.show', $recentMaintenance->asset_id) }}?tab=maintenance" class="asset-feed-item__action" title="View maintenance">
                  <i class="icon-base bx bx-chevron-right icon-20px scaleX-n1-rtl"></i>
                </a>
            </div>
          </li>
          @empty
            <li class="asset-empty-state">
                <div class="asset-empty-state__icon"><i class="icon-base bx bx-wrench"></i></div>
                <div class="asset-empty-state__title">No recent maintenance</div>
                <p class="asset-empty-state__text">Maintenance records will appear here</p>
            </li>
          @endforelse
          
        </ul>
      </div>
    </div>

  </div>

  <div class="col-md-6 order-0 mb-4" >

    <div class="card asset-panel asset-panel--warranty h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div class="asset-panel__header-left">
          <span class="asset-panel__header-icon"><i class="icon-base bx bx-shield-quarter"></i></span>
          <div>
            <h5 class="card-title">Expiring Warranties</h5>
            <small class="asset-panel__subtitle">Warranties ending within 30 days</small>
          </div>
        </div>
        <span class="badge asset-panel__count-badge rounded-pill">{{ count($dashboard['recent_warranties'] ?? []) }}</span>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0 asset-feed-list">
          @forelse ($dashboard['recent_warranties'] as $assetWarranty)
            @php
              $expireDate = $assetWarranty->warranty_expire_date;
              $daysLeft = $expireDate
                  ? (int) now()->startOfDay()->diffInDays($expireDate->copy()->startOfDay(), false)
                  : null;
            @endphp
           <li class="asset-feed-item">
              <div class="asset-feed-item__avatar">
                <i class="icon-base bx bx-shield"></i>
                </div>
              <div class="asset-feed-item__content">
                <h6 class="asset-feed-item__title">{{ $assetWarranty->asset->asset_name ?? '--' }}</h6>
                <div class="asset-feed-item__tags">
                  <span class="asset-feed-tag asset-feed-tag--parts">
                    <i class="icon-base bx bx-list-check"></i>
                    {{ Str::limit($assetWarranty->covered_parts ?? 'N/A', 28) }}
                  </span>
                  <span class="asset-feed-tag asset-feed-tag--expire">
                    <i class="icon-base bx bx-time-five"></i>
                    {{ $expireDate?->format('d M Y') }}
                  </span>
                  @if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7)
                  <span class="asset-feed-tag asset-feed-tag--urgent">
                    <i class="icon-base bx bx-error"></i>
                    {{ $daysLeft === 0 ? 'Expires today' : $daysLeft . ' days left' }}
                  </span>
                  @elseif ($daysLeft !== null && $daysLeft > 7)
                  <span class="asset-feed-tag asset-feed-tag--expire">
                    {{ $daysLeft }} days left
                  </span>
                  @endif
              </div>
              </div>
              <div class="asset-feed-item__aside">
                <a href="{{ route('asset.show', $assetWarranty->asset_id)}}?tab=warranties" class="asset-feed-item__action" title="View warranty">
                  <i class="icon-base bx bx-chevron-right icon-20px scaleX-n1-rtl"></i>
                </a>
            </div>
          </li>

          @empty

            <li class="asset-empty-state">
                <div class="asset-empty-state__icon"><i class="icon-base bx bx-shield-x"></i></div>
                <div class="asset-empty-state__title">No expiring warranties</div>
                <p class="asset-empty-state__text">All warranties are in good standing</p>
            </li>
            
          @endforelse

        </ul>
      </div>
    </div>

  </div>
         
          
      </div>


<div class="row g-4 mb-4 asset-charts-row">

  <div class="col-lg-6 col-md-12">

     <div class="card asset-chart-card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="card-title m-0">Asset Distribution</h5>
            <small>Status breakdown across your portfolio</small>
    </div>
        <span class="asset-panel__header-icon">
            <i class="icon-base bx bx-pie-chart-alt"></i>
        </span>
      </div>
      <div class="card-body">

    <div class="asset-chart-layout">
        <div class="asset-chart-wrap">
            <canvas id="assetStatusChart"></canvas>
  </div>

        <div class="asset-chart-legend" id="assetStatusChartLegend">
            @php
                $chartTotal = max(1, (int) ($dashboard['total_assets'] ?? 0));
                $vibrantChartColors = ['#10b981', '#06b6d4', '#f59e0b', '#f43f5e', '#8b5cf6'];
            @endphp
            @foreach ($assetOverviewChartSeries ?? [] as $series)
            @php
                $seriesValue = (int) ($series['value'] ?? 0);
                $seriesPct = round(($seriesValue / $chartTotal) * 100, 1);
                $vColor = $vibrantChartColors[$loop->index % count($vibrantChartColors)];
            @endphp
            <div class="asset-chart-legend__item"
                 data-chart-index="{{ $loop->index }}"
                 data-chart-target="status"
                 style="--legend-color: {{ $vColor }}; --legend-bg: {{ $vColor }}14; --legend-border: {{ $vColor }}33; --legend-shadow: {{ $vColor }}40;">
                <span class="asset-chart-legend__dot"></span>
                <span class="asset-chart-legend__label">{{ $series['label'] }}</span>
                <div class="asset-chart-legend__stats">
                    <div class="asset-chart-legend__count">{{ number_format($seriesValue) }}</div>
                    <div class="asset-chart-legend__pct">{{ $seriesPct }}%</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="asset-chart-footer">
        Tracking <strong>{{ number_format($dashboard['total_assets'] ?? 0) }}</strong> assets in total
</div>

      </div>
     </div>

  </div>

  <div class="col-lg-6 col-md-12">

     <div class="card asset-chart-card asset-chart-card--value h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="card-title m-0">Asset Value Summary</h5>
            <small>Purchase, current value &amp; depreciation split</small>
        </div>
        <span class="asset-panel__header-icon">
            <i class="icon-base bx bx-wallet"></i>
        </span>
      </div>
      <div class="card-body">

    @php
        $valuePurchase = (float) ($dashboard['total_purchase_value'] ?? 0);
        $valueCurrent = (float) ($dashboard['total_current_value'] ?? 0);
        $valueDepreciation = (float) ($dashboard['total_depreciation'] ?? 0);
        $valueChartTotal = max(0.01, $valuePurchase);
        $assetValueChartSeries = [
            ['label' => 'Current Value', 'value' => $valueCurrent, 'color' => '#06b6d4'],
            ['label' => 'Depreciation', 'value' => $valueDepreciation, 'color' => '#f59e0b'],
        ];
        $valueChartColors = ['#06b6d4', '#f59e0b'];
    @endphp

    <div class="asset-chart-layout">
        <div class="asset-chart-wrap">
            <canvas id="assetValueChart"></canvas>
        </div>

        <div class="asset-chart-legend" id="assetValueChartLegend">
            @foreach ($assetValueChartSeries as $index => $valueSeries)
            @php
                $vAmount = (float) ($valueSeries['value'] ?? 0);
                $vPct = round(($vAmount / $valueChartTotal) * 100, 1);
                $vColor = $valueChartColors[$index % count($valueChartColors)];
            @endphp
            <div class="asset-chart-legend__item"
                 data-chart-index="{{ $index }}"
                 data-chart-target="value"
                 style="--legend-color: {{ $vColor }}; --legend-bg: {{ $vColor }}14; --legend-border: {{ $vColor }}33; --legend-shadow: {{ $vColor }}40;">
                <span class="asset-chart-legend__dot"></span>
                <span class="asset-chart-legend__label">{{ $valueSeries['label'] }}</span>
                <div class="asset-chart-legend__stats">
                    <div class="asset-chart-legend__count">{{ number_format($vAmount, 2) }}</div>
                    <div class="asset-chart-legend__pct">{{ $vPct }}%</div>
                </div>
            </div>
            @endforeach
            <div class="asset-chart-legend__item asset-chart-legend__item--total"
                 style="--legend-color: #6366f1; --legend-bg: rgba(99,102,241,0.08); --legend-border: rgba(99,102,241,0.2);">
                <span class="asset-chart-legend__dot"></span>
                <span class="asset-chart-legend__label">Total Purchase Value</span>
                <div class="asset-chart-legend__stats">
                    <div class="asset-chart-legend__count">{{ number_format($valuePurchase, 2) }}</div>
                    <div class="asset-chart-legend__pct">100%</div>
                </div>
            </div>
      </div>
     </div>

    <div class="asset-chart-footer">
        Depreciation rate <strong>{{ $dashboard['depreciation_percentage'] ?? 0 }}%</strong> of purchase value
  </div>

      </div>
     </div>

  </div>


</div>

</div>

    
@endsection

@section('page-script') 

<script>
(function () {
    const vibrantPalette = ['#10b981', '#06b6d4', '#f59e0b', '#f43f5e', '#8b5cf6'];
    const valuePalette = ['#06b6d4', '#f59e0b'];

    function formatMoney(value) {
        const num = typeof value === 'number' ? value : Number(value);
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function createCenterTextPlugin(centerValue, centerLabel, gradientStops) {
        return {
            id: 'assetChartCenterText_' + centerLabel.replace(/\s+/g, '_'),
            afterDraw(chart) {
                const { ctx: canvasCtx, chartArea } = chart;
                if (!chartArea) return;

                const centerX = (chartArea.left + chartArea.right) / 2;
                const centerY = (chartArea.top + chartArea.bottom) / 2;

                canvasCtx.save();
                canvasCtx.textAlign = 'center';
                canvasCtx.textBaseline = 'middle';

                const gradient = canvasCtx.createLinearGradient(centerX - 40, centerY, centerX + 40, centerY);
                gradient.addColorStop(0, gradientStops[0]);
                gradient.addColorStop(1, gradientStops[1]);
                canvasCtx.fillStyle = gradient;
                const isCompact = chartArea.width < 185;
                const valueFontSize = isCompact ? '1.25rem' : '1.5rem';
                canvasCtx.font = '800 ' + valueFontSize + ' Inter, system-ui, sans-serif';
                canvasCtx.fillText(centerValue, centerX, centerY - (isCompact ? 6 : 8));

                canvasCtx.fillStyle = '#94a3b8';
                const labelFontSize = isCompact ? '0.58rem' : '0.65rem';
                canvasCtx.font = '600 ' + labelFontSize + ' Inter, system-ui, sans-serif';
                canvasCtx.fillText(centerLabel, centerX, centerY + (isCompact ? 12 : 16));
                canvasCtx.restore();
            },
        };
    }

    function bindLegend(chart, selector) {
        document.querySelectorAll(selector).forEach((item) => {
            item.addEventListener('mouseenter', () => {
                const index = Number(item.dataset.chartIndex);
                if (Number.isNaN(index)) return;

                document.querySelectorAll(selector).forEach((el) => el.classList.remove('is-active'));
                item.classList.add('is-active');
                chart.setActiveElements([{ datasetIndex: 0, index }]);
                chart.tooltip.setActiveElements([{ datasetIndex: 0, index }], { x: 0, y: 0 });
                chart.update();
            });
            item.addEventListener('mouseleave', () => {
                item.classList.remove('is-active');
                chart.setActiveElements([]);
                chart.tooltip.setActiveElements([]);
                chart.update();
            });
        });
    }

    function initDoughnutChart(canvasId, series, options) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !series.length) return null;

        const labels = series.map((s) => s.label);
        const data = series.map((s) => Number(s.value));
        const colors = series.map((s, i) => s.color || options.palette[i % options.palette.length]);
        const total = options.total;

        const chart = new Chart(canvas, {
            type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                    borderWidth: 5,
                borderColor: '#fff',
                    hoverBorderWidth: 5,
                    hoverOffset: 14,
                    spacing: 3,
                    borderRadius: 4,
                }],
            },
            plugins: [createCenterTextPlugin(options.centerText, options.centerLabel, options.gradientStops)],
        options: {
            responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                layout: { padding: 8 },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                },
                onHover(event, elements) {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: { display: false },
                tooltip: {
                        backgroundColor: 'rgba(67, 89, 113, 0.96)',
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 14,
                        cornerRadius: 10,
                    callbacks: {
                        label(context) {
                            const raw = context.raw;
                            const value = typeof raw === 'number' ? raw : Number(raw);
                                const pct = total > 0
                                    ? ((value / total) * 100).toFixed(1)
                                : '0.0';
                                const formatted = options.isCurrency
                                    ? formatMoney(value)
                                    : value.toLocaleString();
                                return ` ${context.label}: ${formatted} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });

        if (options.legendSelector) {
            bindLegend(chart, options.legendSelector);
        }

        return chart;
    }

    const assetOverviewChartSeries = {!! json_encode($assetOverviewChartSeries ?? []) !!};
    const totalAssets = {{ (int) ($dashboard['total_assets'] ?? 0) }};

    initDoughnutChart('assetStatusChart', assetOverviewChartSeries, {
        palette: vibrantPalette,
        total: Math.max(1, totalAssets),
        centerText: totalAssets.toLocaleString(),
        centerLabel: 'TOTAL ASSETS',
        gradientStops: ['#6366f1', '#06b6d4'],
        legendSelector: '#assetStatusChartLegend .asset-chart-legend__item[data-chart-target="status"]',
        isCurrency: false,
    });

    const assetValueChartSeries = {!! json_encode($assetValueChartSeries ?? []) !!};
    const valuePurchase = {{ (float) ($dashboard['total_purchase_value'] ?? 0) }};

    initDoughnutChart('assetValueChart', assetValueChartSeries, {
        palette: valuePalette,
        total: Math.max(0.01, valuePurchase),
        centerText: formatMoney(valuePurchase),
        centerLabel: 'PURCHASE VALUE',
        gradientStops: ['#6366f1', '#f59e0b'],
        legendSelector: '#assetValueChartLegend .asset-chart-legend__item[data-chart-target="value"]',
        isCurrency: true,
    });

    const assetAuditChartSeries = {!! json_encode($assetAuditChartSeries ?? []) !!};
    const totalAuditAssets = {{ (int) ($dashboard['total_assets'] ?? 0) }};

    initDoughnutChart('assetAuditChart', assetAuditChartSeries, {
        palette: ['#10b981', '#f43f5e', '#f59e0b'],
        total: Math.max(1, totalAuditAssets),
        centerText: totalAuditAssets.toLocaleString(),
        centerLabel: 'TOTAL ASSETS',
        gradientStops: ['#10b981', '#06b6d4'],
        legendSelector: '#assetAuditChartLegend .asset-chart-legend__item[data-chart-target="audit"]',
        isCurrency: false,
    });

    const branchDropdown = document.getElementById('branch_id');
    const companyDropdown = document.getElementById('company_id');
    const initialBranch = "{{ request('branch_id') }}";

    function loadBranches(companyId, selectedId = '') {
        if (!branchDropdown) return;

        branchDropdown.innerHTML = '<option value="">Loading...</option>';
        branchDropdown.disabled = true;

            if (!companyId) {
            branchDropdown.innerHTML = '<option value="">All Branchs</option>';
            branchDropdown.disabled = false;
                return;
            }

        fetch("{{ route('ajax.getbranch.bycompany', ':id') }}".replace(':id', companyId))
            .then((res) => res.json())
            .then((data) => {
                branchDropdown.innerHTML = '<option value="">All Branchs</option>';
                if (data.data && data.data.length > 0) {
                    data.data.forEach((branch) => {
                        const option = document.createElement('option');
                        option.value = branch.id;
                        option.textContent = branch.name;
                        if (String(selectedId) === String(branch.id)) {
                            option.selected = true;
                        }
                        branchDropdown.appendChild(option);
                        });
                    } else {
                    branchDropdown.innerHTML = '<option value="">No branches found</option>';
                }
            })
            .catch(() => {
                branchDropdown.innerHTML = '<option value="">Failed to load branches</option>';
            })
            .finally(() => {
                branchDropdown.disabled = false;
            });
    }

    if (companyDropdown) {
        companyDropdown.addEventListener('change', function () {
            loadBranches(this.value);
        });

        if (companyDropdown.value) {
            loadBranches(companyDropdown.value, initialBranch);
        }
    }
})();
</script>

@endsection
