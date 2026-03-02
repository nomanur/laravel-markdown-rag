@php
    $data = $data ?? [];
@endphp

<div class="rm-rag-viz-container">
    <div class="rm-rag-viz-header">
        <div class="rm-rag-viz-title-group">
            <h1 class="rm-rag-viz-title">Vector Knowledge Map</h1>
            <p class="rm-rag-viz-subtitle">2D Visualization of your Knowledge Base Embeddings</p>
        </div>
        <div class="rm-rag-viz-metrics">
            <div class="rm-rag-viz-metric">
                <span class="rm-rag-viz-metric-label">Total Chunks</span>
                <span class="rm-rag-viz-metric-value">{{ count($data) }}</span>
            </div>
            <div class="rm-rag-viz-metric">
                <span class="rm-rag-viz-metric-label">Algorithm</span>
                <span class="rm-rag-viz-metric-value">t-SNE</span>
            </div>
        </div>
    </div>

    <div class="rm-rag-viz-card">
        <div id="vector-plot" class="rm-rag-plot"></div>
        @if(empty($data))
            <div class="rm-rag-viz-empty">
                <div class="rm-rag-viz-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A2 2 0 013 15.483V6a2 2 0 011.023-1.743l1.352-.676a2 2 0 011.91 0l6.715 3.357a2 2 0 010 3.576l-6.715 3.357a2 2 0 01-1.91 0L4 13.517V15.483l5.447 2.724a2 2 0 001.106 0L16 15.483V13.517l-1.447.724a2 2 0 01-1.91 0L5.928 10.884a2 2 0 010-3.576l6.715-3.357a2 2 0 011.91 0l6.715 3.357A2 2 0 0122 6v9.483a2 2 0 01-1.023 1.743L15.528 20a2 2 0 01-1.106 0z"></path>
                    </svg>
                </div>
                <h3>No Embeddings Found</h3>
                <p>Run <code>php artisan knowledge:index</code> to populate your knowledge base.</p>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.plot.ly/plotly-2.27.0.min.js" charset="utf-8"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rawData = @json($data);
        if (rawData.length === 0) return;

        const trace = {
            x: rawData.map(d => d.x),
            y: rawData.map(d => d.y),
            text: rawData.map(d => d.text),
            customdata: rawData.map(d => d.source),
            mode: 'markers',
            type: 'scatter',
            hoverinfo: 'text',
            hovertemplate: 
                '<b>Source:</b> %{customdata}<br>' +
                '<b>Content:</b> %{text}<br>' +
                '<extra></extra>',
            marker: {
                size: 10,
                color: rawData.map((d, i) => i),
                colorscale: 'Viridis',
                opacity: 0.8,
                line: {
                    color: 'rgba(255, 255, 255, 0.5)',
                    width: 1
                }
            }
        };

        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        const layout = {
            paper_bgcolor: 'rgba(0,0,0,0)',
            plot_bgcolor: 'rgba(0,0,0,0)',
            font: {
                family: 'Inter, sans-serif',
                color: isDark ? '#94a3b8' : '#64748b'
            },
            margin: { t: 40, b: 40, l: 40, r: 40 },
            hovermode: 'closest',
            xaxis: { showgrid: false, zeroline: false, showticklabels: false },
            yaxis: { showgrid: false, zeroline: false, showticklabels: false },
            showlegend: false
        };

        const config = {
            responsive: true,
            displayModeBar: false
        };

        Plotly.newPlot('vector-plot', [trace], layout, config);
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    :root {
        --viz-bg: #f8fafc;
        --viz-card: #ffffff;
        --viz-border: #e2e8f0;
        --viz-text: #1e293b;
        --viz-text-muted: #64748b;
        --viz-accent: #6366f1;
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --viz-bg: #09090b;
            --viz-card: #18181b;
            --viz-border: #27272a;
            --viz-text: #f1f5f9;
            --viz-text-muted: #94a3b8;
        }
    }

    .rm-rag-viz-container {
        font-family: 'Inter', system-ui, sans-serif;
        background-color: var(--viz-bg);
        min-height: 100vh;
        padding: 2rem;
        color: var(--viz-text);
    }

    .rm-rag-viz-header {
        max-width: 80rem;
        margin: 0 auto 2rem auto;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .rm-rag-viz-title {
        font-size: 1.875rem;
        font-weight: 800;
        letter-spacing: -0.025em;
        margin: 0;
    }

    .rm-rag-viz-subtitle {
        color: var(--viz-text-muted);
        margin: 0.25rem 0 0 0;
    }

    .rm-rag-viz-metrics {
        display: flex;
        gap: 2rem;
    }

    .rm-rag-viz-metric {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .rm-rag-viz-metric-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--viz-text-muted);
    }

    .rm-rag-viz-metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--viz-accent);
    }

    .rm-rag-viz-card {
        max-width: 80rem;
        margin: 0 auto;
        background-color: var(--viz-card);
        border: 1px solid var(--viz-border);
        border-radius: 1.5rem;
        height: 600px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .rm-rag-plot {
        width: 100%;
        height: 100%;
    }

    .rm-rag-viz-empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
    }

    .rm-rag-viz-empty-icon {
        width: 4rem;
        height: 4rem;
        color: var(--viz-text-muted);
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .rm-rag-viz-empty code {
        background: rgba(99, 102, 241, 0.1);
        color: var(--viz-accent);
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-family: inherit;
    }

    @media (max-width: 768px) {
        .rm-rag-viz-header { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
        .rm-rag-viz-metrics { align-self: stretch; justify-content: space-between; }
        .rm-rag-viz-metric { align-items: flex-start; }
    }
</style>
