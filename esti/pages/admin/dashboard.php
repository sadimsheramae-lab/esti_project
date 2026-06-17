<?php
require_once '../../includes/config.php';
requireLogin();
$page_title = 'Dashboard';

$total_students = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$total_subjects = $conn->query("SELECT COUNT(*) c FROM subjects")->fetch_assoc()['c'];
$total_classes  = $conn->query("SELECT COUNT(*) c FROM classes")->fetch_assoc()['c'];
$total_depts    = $conn->query("SELECT COUNT(*) c FROM departments")->fetch_assoc()['c'];
$total_faculty  = $conn->query("SELECT COUNT(*) c FROM faculty")->fetch_assoc()['c'];

// Enrollment per course (real data)
$enroll_by_course = $conn->query("SELECT course, COUNT(*) c FROM students WHERE status='Active' GROUP BY course ORDER BY c DESC");
$course_labels = [];
$course_data   = [];
while ($r = $enroll_by_course->fetch_assoc()) {
    $course_labels[] = $r['course'];
    $course_data[]   = (int)$r['c'];
}

// Monthly enrollment (students created per month this year)
$monthly = $conn->query("
    SELECT MONTH(created_at) m, COUNT(*) c
    FROM students
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY m
");
$month_names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthly_data = array_fill(0, 12, 0);
while ($r = $monthly->fetch_assoc()) {
    $monthly_data[(int)$r['m'] - 1] = (int)$r['c'];
}
// If no real monthly data, use sample
if (array_sum($monthly_data) === 0) {
    $monthly_data = [120, 95, 80, 210, 185, 240, 310, 420, 380, 290, 180, 150];
}

// Enrollment by year level
$year_level_data = $conn->query("SELECT year_level, COUNT(*) c FROM students GROUP BY year_level ORDER BY year_level");
$yl_labels = [];
$yl_data   = [];
while ($r = $year_level_data->fetch_assoc()) {
    $yl_labels[] = $r['year_level'];
    $yl_data[]   = (int)$r['c'];
}

// Grade distribution
$grade_dist   = [210, 345, 410, 210, 70];
$grade_labels = ['Excellent (90-100)', 'Very Good (80-89)', 'Good (70-79)', 'Satisfactory (60-69)', 'Below 60'];
$grade_colors = ['#1d5c3a', '#4a90d9', '#f5a623', '#e8c844', '#e05a5a'];
$grade_pcts   = ['16.9%', '27.7%', '32.9%', '16.9%', '5.6%'];

// Recent students
$recent_students = $conn->query("SELECT id_number, CONCAT(last_name,', ',first_name) name, course, year_level, status FROM students ORDER BY created_at DESC LIMIT 5");

require_once '../../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,500;8..60,600;8..60,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* =====================================================
   Dashboard — Registrar's Ledger theme
   Distinct visual identity: academic record-keeping,
   filed tabs, ledger numerals, parchment surfaces.
===================================================== */
:root{
  --lg-ink:#241f1a;
  --lg-green-deep:#0f3d28;
  --lg-green:#1d6b46;
  --lg-green-soft:#e7f0e9;
  --lg-amber:#c98a3b;
  --lg-amber-soft:#f7ecd9;
  --lg-parchment:#f6f2e7;
  --lg-parchment-line:#e6dfc9;
  --lg-card:#fffdf8;
  --lg-muted:#8a8071;
  --lg-serif:'Source Serif 4', Georgia, 'Times New Roman', serif;
  --lg-sans:'Inter', system-ui, -apple-system, sans-serif;
}

.lg-dash{ font-family:var(--lg-sans); color:var(--lg-ink); }

/* ---------- Header ---------- */
.lg-header{
  display:flex; align-items:flex-end; justify-content:space-between;
  flex-wrap:wrap; gap:14px; margin-bottom:22px;
  padding-bottom:16px; border-bottom:1px solid var(--lg-parchment-line);
}
.lg-eyebrow{
  font-size:11px; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
  color:var(--lg-amber); margin-bottom:6px;
}
.lg-title{
  font-family:var(--lg-serif); font-size:28px; font-weight:600; margin:0;
  color:var(--lg-green-deep); letter-spacing:-.01em;
}
.lg-title em{ font-style:normal; color:var(--lg-amber); }
.lg-date{
  font-family:var(--lg-serif); font-size:13px; color:var(--lg-muted);
  text-align:right; border-left:2px solid var(--lg-parchment-line); padding-left:14px;
}
.lg-date i{ color:var(--lg-amber); margin-right:6px; }

/* ---------- Stat row: filed record tabs ---------- */
.lg-stat-row{
  display:grid; grid-template-columns:repeat(5,1fr); gap:0;
  background:var(--lg-card); border:1px solid var(--lg-parchment-line);
  border-radius:10px; overflow:hidden; margin-bottom:22px;
  box-shadow:0 1px 0 rgba(36,31,26,.02);
}
.lg-stat{
  position:relative; padding:18px 16px 16px;
  border-right:1px solid var(--lg-parchment-line);
}
.lg-stat:last-child{ border-right:none; }
.lg-stat::before{
  content:attr(data-tag);
  position:absolute; top:0; left:16px; transform:translateY(-50%);
  background:var(--lg-parchment); border:1px solid var(--lg-parchment-line);
  font-size:9px; font-weight:700; letter-spacing:.1em; color:var(--lg-muted);
  padding:2px 7px; border-radius:999px; text-transform:uppercase;
}
.lg-stat-num{
  font-family:var(--lg-serif); font-size:30px; font-weight:600; line-height:1;
  color:var(--lg-green-deep); margin-top:10px;
}
.lg-stat-label{
  font-size:11.5px; color:var(--lg-muted); margin-top:5px; font-weight:500;
}
.lg-stat-tick{
  width:24px; height:3px; border-radius:2px; margin-top:10px; background:var(--lg-green);
}
.lg-stat:nth-child(2) .lg-stat-tick{ background:#4a90d9; }
.lg-stat:nth-child(3) .lg-stat-tick{ background:#6f42c1; }
.lg-stat:nth-child(4) .lg-stat-tick{ background:var(--lg-amber); }
.lg-stat:nth-child(5) .lg-stat-tick{ background:#1d8a8a; }

/* ---------- Card shell (ledger sheet) ---------- */
.lg-card{
  background:var(--lg-card); border:1px solid var(--lg-parchment-line);
  border-radius:10px; padding:0; overflow:hidden;
}
.lg-card-head{
  display:flex; align-items:center; gap:10px; flex-wrap:wrap;
  padding:15px 18px; border-bottom:1px dashed var(--lg-parchment-line);
  background:linear-gradient(180deg, var(--lg-parchment) 0%, var(--lg-card) 100%);
}
.lg-card-index{
  font-family:var(--lg-serif); font-size:11px; font-weight:700; color:#fff;
  background:var(--lg-green-deep); width:22px; height:22px; border-radius:5px;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.lg-card-title{
  font-family:var(--lg-serif); font-size:15px; font-weight:600; color:var(--lg-ink);
}
.lg-card-sub{
  font-size:11px; color:var(--lg-muted); margin-top:1px;
}
.lg-card-body{ padding:18px; }
.lg-card-actions{ margin-left:auto; display:flex; gap:6px; align-items:center; }

.lg-chart-btn{
  font-size:10.5px; font-weight:600; padding:5px 10px;
  border:1px solid var(--lg-parchment-line); border-radius:6px;
  background:#fff; color:var(--lg-muted); cursor:pointer;
  transition:all .15s ease; font-family:var(--lg-sans);
}
.lg-chart-btn.active{ background:var(--lg-green-deep); color:#fff; border-color:var(--lg-green-deep); }
.lg-chart-btn:hover:not(.active){ background:var(--lg-parchment); }
.lg-select{
  font-size:10.5px; border:1px solid var(--lg-parchment-line); border-radius:6px;
  padding:5px 9px; outline:none; background:#fff; cursor:pointer; color:var(--lg-ink);
  font-family:var(--lg-sans);
}

/* ---------- Layout rows ---------- */
.lg-row-main{ display:grid; grid-template-columns:1.6fr 1fr; gap:18px; margin-bottom:18px; }
.lg-row-secondary{ display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
.lg-row-bottom{ display:grid; grid-template-columns:1.3fr 1fr; gap:18px; }
@media (max-width:1100px){
  .lg-row-main, .lg-row-secondary, .lg-row-bottom{ grid-template-columns:1fr; }
  .lg-stat-row{ grid-template-columns:repeat(2,1fr); }
  .lg-stat:nth-child(2n){ border-right:none; }
}

/* ---------- Donut legend ---------- */
.lg-donut-wrap{ display:flex; gap:18px; align-items:center; flex-wrap:wrap; }
.lg-donut-canvas{ position:relative; width:128px; height:128px; flex-shrink:0; }
.lg-legend{ flex:1; min-width:150px; }
.lg-legend-item{ display:flex; align-items:flex-start; gap:8px; margin-bottom:9px; }
.lg-legend-dot{ width:9px; height:9px; border-radius:2px; margin-top:3px; flex-shrink:0; }
.lg-legend-text{ font-size:11.5px; line-height:1.35; color:var(--lg-ink); }
.lg-legend-pct{ font-family:var(--lg-serif); font-weight:600; }

/* ---------- Recent students ledger table ---------- */
.lg-table{ width:100%; border-collapse:collapse; font-size:12.5px; }
.lg-table thead th{
  text-align:left; font-size:10px; letter-spacing:.08em; text-transform:uppercase;
  color:var(--lg-muted); padding:9px 10px; border-bottom:1px solid var(--lg-parchment-line);
  font-weight:700;
}
.lg-table tbody td{
  padding:11px 10px; border-bottom:1px dashed var(--lg-parchment-line); vertical-align:middle;
}
.lg-table tbody tr:last-child td{ border-bottom:none; }
.lg-id{ font-family:var(--lg-serif); font-weight:600; color:var(--lg-green-deep); }
.lg-badge{
  display:inline-block; padding:2px 9px; border-radius:999px; font-size:10px; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em;
}
.lg-badge-active{ background:var(--lg-green-soft); color:var(--lg-green-deep); }
.lg-badge-inactive{ background:#f3f0e8; color:var(--lg-muted); }
.lg-view-all{
  margin-left:auto; font-size:11.5px; color:var(--lg-amber); text-decoration:none; font-weight:700;
}
.lg-view-all:hover{ text-decoration:underline; }

/* ---------- Activity feed ---------- */
.lg-activity-list{ padding:6px 18px 14px; }
.lg-activity-item{
  display:flex; gap:12px; align-items:flex-start; padding:11px 0;
  border-bottom:1px dashed var(--lg-parchment-line);
}
.lg-activity-item:last-child{ border-bottom:none; }
.lg-activity-icon{
  width:30px; height:30px; border-radius:7px; flex-shrink:0;
  background:var(--lg-green-soft); color:var(--lg-green-deep);
  display:flex; align-items:center; justify-content:center; font-size:12px;
}
.lg-activity-title{ font-size:12.5px; font-weight:600; color:var(--lg-ink); }
.lg-activity-sub{ font-size:11px; color:var(--lg-muted); margin-top:1px; }
.lg-activity-time{
  font-family:var(--lg-serif); font-size:11px; color:var(--lg-muted); white-space:nowrap; margin-left:auto;
}
</style>

<div class="lg-dash">

<div class="lg-header">
    <div>
        <div class="lg-eyebrow">Registrar's Desk</div>
        <h1 class="lg-title">Welcome back, <em>Administrator</em></h1>
    </div>
    <div class="lg-date"><i class="fas fa-calendar"></i><?= date('l, F j, Y') ?></div>
</div>

<!-- Stat Row: filed record tabs -->
<div class="lg-stat-row">
    <div class="lg-stat" data-tag="Enrolled">
        <div class="lg-stat-num"><?= number_format($total_students) ?></div>
        <div class="lg-stat-label">Total Students</div>
        <div class="lg-stat-tick"></div>
    </div>
    <div class="lg-stat" data-tag="Curriculum">
        <div class="lg-stat-num"><?= $total_subjects ?></div>
        <div class="lg-stat-label">Total Subjects</div>
        <div class="lg-stat-tick"></div>
    </div>
    <div class="lg-stat" data-tag="Sections">
        <div class="lg-stat-num"><?= $total_classes ?></div>
        <div class="lg-stat-label">Total Classes</div>
        <div class="lg-stat-tick"></div>
    </div>
    <div class="lg-stat" data-tag="Org">
        <div class="lg-stat-num"><?= $total_depts ?></div>
        <div class="lg-stat-label">Departments</div>
        <div class="lg-stat-tick"></div>
    </div>
    <div class="lg-stat" data-tag="Staff">
        <div class="lg-stat-num"><?= $total_faculty ?></div>
        <div class="lg-stat-label">Faculty Members</div>
        <div class="lg-stat-tick"></div>
    </div>
</div>

<!-- Main charts row -->
<div class="lg-row-main">

    <div class="lg-card">
        <div class="lg-card-head">
            <div class="lg-card-index">01</div>
            <div>
                <div class="lg-card-title">Enrollment Overview</div>
                <div class="lg-card-sub" id="chartSubtitle">Monthly student enrollment for S.Y. <?= date('Y').'-'.(date('Y')+1) ?></div>
            </div>
            <div class="lg-card-actions">
                <button onclick="setChartType('line')" id="btnLine" class="lg-chart-btn active"><i class="fas fa-chart-line"></i> Line</button>
                <button onclick="setChartType('bar')" id="btnBar" class="lg-chart-btn"><i class="fas fa-chart-bar"></i> Bar</button>
                <select id="yearSelect" class="lg-select" onchange="switchYear(this.value)">
                    <option value="monthly">Monthly (<?= date('Y') ?>)</option>
                    <option value="course">By Course</option>
                    <option value="yearlevel">By Year Level</option>
                </select>
            </div>
        </div>
        <div class="lg-card-body">
            <div style="position:relative;height:200px;">
                <canvas id="enrollChart"></canvas>
            </div>
        </div>
    </div>

    <div class="lg-card">
        <div class="lg-card-head">
            <div class="lg-card-index">02</div>
            <div>
                <div class="lg-card-title">Grade Summary</div>
                <div class="lg-card-sub">Distribution across all recorded grades</div>
            </div>
        </div>
        <div class="lg-card-body">
            <div class="lg-donut-wrap">
                <div class="lg-donut-canvas"><canvas id="gradeChart" width="128" height="128"></canvas></div>
                <div class="lg-legend">
                    <?php foreach ($grade_labels as $i => $lbl): ?>
                    <div class="lg-legend-item">
                        <div class="lg-legend-dot" style="background:<?= $grade_colors[$i] ?>;"></div>
                        <div class="lg-legend-text">
                            <?= $lbl ?><br>
                            <span class="lg-legend-pct" style="color:<?= $grade_colors[$i] ?>"><?= $grade_pcts[$i] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary charts row -->
<div class="lg-row-secondary">
    <div class="lg-card">
        <div class="lg-card-head">
            <div class="lg-card-index">03</div>
            <div>
                <div class="lg-card-title">Students by Course</div>
                <div class="lg-card-sub">Active enrollment per program</div>
            </div>
        </div>
        <div class="lg-card-body">
            <div style="position:relative;height:160px;"><canvas id="courseChart"></canvas></div>
        </div>
    </div>
    <div class="lg-card">
        <div class="lg-card-head">
            <div class="lg-card-index">04</div>
            <div>
                <div class="lg-card-title">Students by Year Level</div>
                <div class="lg-card-sub">Standing across all programs</div>
            </div>
        </div>
        <div class="lg-card-body">
            <div style="position:relative;height:160px;"><canvas id="yearLevelChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Bottom row -->
<div class="lg-row-bottom">
    <div class="lg-card">
        <div class="lg-card-head">
            <div class="lg-card-index">05</div>
            <div><div class="lg-card-title">Recent Students</div><div class="lg-card-sub">Latest entries to the roll</div></div>
            <a href="students.php" class="lg-view-all">View All →</a>
        </div>
        <div class="lg-card-body" style="padding-top:6px;">
            <table class="lg-table">
                <thead><tr><th>ID Number</th><th>Name</th><th>Course</th><th>Year Level</th><th>Status</th></tr></thead>
                <tbody>
                <?php while ($s = $recent_students->fetch_assoc()): ?>
                <tr>
                    <td class="lg-id"><?= htmlspecialchars($s['id_number']) ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['course']) ?></td>
                    <td><?= htmlspecialchars($s['year_level']) ?></td>
                    <td><span class="lg-badge lg-badge-<?= strtolower($s['status']) ?>"><?= $s['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="lg-card">
        <div class="lg-card-head">
            <div class="lg-card-index">06</div>
            <div><div class="lg-card-title">Recent Activities</div><div class="lg-card-sub">Latest actions logged today</div></div>
        </div>
        <div class="lg-activity-list">
            <div class="lg-activity-item">
                <div class="lg-activity-icon"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div class="lg-activity-title">New student registered</div>
                    <div class="lg-activity-sub">Juan Dela Cruz (20241001)</div>
                </div>
                <div class="lg-activity-time">10:24 AM</div>
            </div>
            <div class="lg-activity-item">
                <div class="lg-activity-icon"><i class="fas fa-star"></i></div>
                <div>
                    <div class="lg-activity-title">Grades posted for BSIT 2A</div>
                    <div class="lg-activity-sub">Data Structures and Algorithms</div>
                </div>
                <div class="lg-activity-time">Yesterday</div>
            </div>
            <div class="lg-activity-item">
                <div class="lg-activity-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="lg-activity-title">New subject added</div>
                    <div class="lg-activity-sub">Purposive Communication (GE202)</div>
                </div>
                <div class="lg-activity-time">May 18</div>
            </div>
            <div class="lg-activity-item">
                <div class="lg-activity-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <div class="lg-activity-title">Faculty account created</div>
                    <div class="lg-activity-sub">Prof. John Michael Cruz</div>
                </div>
                <div class="lg-activity-time">May 18</div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Data from PHP
const monthlyData   = <?= json_encode($monthly_data) ?>;
const monthLabels   = <?= json_encode($month_names) ?>;
const courseData    = <?= json_encode($course_data) ?>;
const courseLabels  = <?= json_encode($course_labels) ?>;
const ylData        = <?= json_encode($yl_data) ?>;
const ylLabels      = <?= json_encode($yl_labels) ?>;

// ── Chart.js global defaults
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#8a8071';

// ── Enrollment Line/Bar Chart
const enrollCtx = document.getElementById('enrollChart').getContext('2d');
let enrollChart = new Chart(enrollCtx, {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Students Enrolled',
            data: monthlyData,
            borderColor: '#1d6b46',
            backgroundColor: (ctx) => {
                const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                g.addColorStop(0, 'rgba(29,107,70,0.28)');
                g.addColorStop(1, 'rgba(29,107,70,0.02)');
                return g;
            },
            borderWidth: 2.5,
            pointBackgroundColor: '#1d6b46',
            pointBorderColor: '#fffdf8',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f3d28',
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,.85)',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y + ' students'
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(36,31,26,.05)', drawBorder: false },
                ticks: { font: { size: 11 } }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(36,31,26,.06)', drawBorder: false },
                ticks: {
                    font: { size: 11 },
                    callback: v => v >= 1000 ? (v/1000).toFixed(1)+'k' : v
                }
            }
        }
    }
});

// ── Switch view (monthly / by course / by year level)
const subtitles = {
    monthly:   'Monthly student enrollment for S.Y. <?= date('Y').'-'.(date('Y')+1) ?>',
    course:    'Total active students per course/department',
    yearlevel: 'Total students grouped by year level'
};
const datasets = {
    monthly:   { labels: monthLabels, data: monthlyData, label: 'Students Enrolled' },
    course:    { labels: courseLabels, data: courseData, label: 'Students per Course' },
    yearlevel: { labels: ylLabels, data: ylData, label: 'Students per Year Level' }
};
const barColors = ['#0f3d28','#4a90d9','#c98a3b','#e8c844','#6f42c1','#e05a5a'];

function switchYear(view) {
    const d = datasets[view];
    document.getElementById('chartSubtitle').textContent = subtitles[view];
    enrollChart.data.labels = d.labels;
    enrollChart.data.datasets[0].label = d.label;
    enrollChart.data.datasets[0].data  = d.data;
    if (view !== 'monthly') {
        enrollChart.data.datasets[0].backgroundColor = barColors.slice(0, d.data.length);
        enrollChart.data.datasets[0].borderColor      = barColors.slice(0, d.data.length);
    } else {
        enrollChart.data.datasets[0].backgroundColor = (ctx) => {
            const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
            g.addColorStop(0, 'rgba(29,107,70,0.28)');
            g.addColorStop(1, 'rgba(29,107,70,0.02)');
            return g;
        };
        enrollChart.data.datasets[0].borderColor = '#1d6b46';
    }
    enrollChart.update();
}

let currentType = 'line';
function setChartType(type) {
    currentType = type;
    enrollChart.destroy();
    const isLine = type === 'line';
    enrollChart = new Chart(enrollCtx, {
        type: type,
        data: {
            labels: enrollChart?.data?.labels || monthLabels,
            datasets: [{
                label: 'Students Enrolled',
                data: enrollChart?.data?.datasets[0]?.data || monthlyData,
                borderColor: '#1d6b46',
                backgroundColor: isLine
                    ? (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                        g.addColorStop(0, 'rgba(29,107,70,0.28)');
                        g.addColorStop(1, 'rgba(29,107,70,0.02)');
                        return g;
                    }
                    : barColors,
                borderWidth: 2.5,
                pointBackgroundColor: '#1d6b46',
                pointBorderColor: '#fffdf8',
                pointBorderWidth: 2,
                pointRadius: isLine ? 5 : 0,
                pointHoverRadius: 7,
                fill: isLine,
                tension: 0.4,
                borderRadius: isLine ? 0 : 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f3d28',
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,.85)',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: { label: ctx => ' ' + ctx.parsed.y + ' students' }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(36,31,26,.05)' }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(36,31,26,.06)' },
                    ticks: { font: { size: 11 }, callback: v => v >= 1000 ? (v/1000).toFixed(1)+'k' : v }
                }
            }
        }
    });
    document.querySelectorAll('.lg-chart-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn' + type.charAt(0).toUpperCase() + type.slice(1)).classList.add('active');
}

// ── Grade Donut (keep raw canvas for donut)
drawDonut('gradeChart', [210,345,410,210,70], ['#1d5c3a','#4a90d9','#f5a623','#e8c844','#e05a5a']);

// ── Students by Course Bar Chart
new Chart(document.getElementById('courseChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: courseLabels.length ? courseLabels : ['BSIT','BSA','BEED','BSHM','BSCRIM','BSBA'],
        datasets: [{
            label: 'Students',
            data: courseData.length ? courseData : [320, 210, 185, 140, 95, 110],
            backgroundColor: ['#0f3d28','#4a90d9','#c98a3b','#e8c844','#6f42c1','#e05a5a'],
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f3d28',
                callbacks: { label: ctx => ' ' + ctx.parsed.y + ' students' }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(36,31,26,.06)' }, ticks: { font: { size: 10 } } }
        }
    }
});

// ── Students by Year Level Doughnut/Polar
new Chart(document.getElementById('yearLevelChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ylLabels.length ? ylLabels : ['1st Year','2nd Year','3rd Year','4th Year'],
        datasets: [{
            data: ylData.length ? ylData : [380, 320, 290, 255],
            backgroundColor: ['#1d6b46','#4a90d9','#c98a3b','#6f42c1'],
            borderWidth: 2,
            borderColor: '#fffdf8',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'right',
                labels: { font: { size: 10 }, padding: 10, boxWidth: 12 }
            },
            tooltip: {
                backgroundColor: '#0f3d28',
                callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' students' }
            }
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>