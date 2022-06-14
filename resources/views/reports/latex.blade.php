\documentclass[12pt, portuguese, a4paper, pdftex, fleqn]{article}
\usepackage{adjustbox}
\usepackage[portuguese]{babel}
\usepackage[scaled=.92]{helvet}
\usepackage{fancyhdr}
\usepackage{float}
\usepackage{indentfirst}
\usepackage[hidelinks]{hyperref}
\usepackage[svgnames,table]{xcolor}
\usepackage{booktabs, makecell, longtable}
\usepackage[a4paper,inner=1.5cm,outer=1.5cm,top=1cm,bottom=1cm,bindingoffset=0cm]{geometry}
\usepackage{blindtext}
\geometry{textwidth=\paperwidth, textheight=\paperheight, noheadfoot, nomarginpar}

\renewcommand{\familydefault}{\sfdefault}

\pagestyle{fancy}
\fancyhead{}
\renewcommand{\headrulewidth}{0pt}

\begin{document}
\begin{titlepage}

\begin{center}
  
\begin{minipage}{0.3\textwidth}
\begin{figure}[H]
 \includegraphics[scale=0.2]{{!! base_path() . "/logo_ime.jpg" !!}}
\end{figure}
\end{minipage} \hfill
\begin{minipage}{0.2\textwidth}
\begin{figure}[H]
 \includegraphics[scale=0.55]{{!! base_path() . "/logo_usp.jpg" !!}}
\end{figure}
\end{minipage}\\[8cm]
     
   {\Large \textbf{Resumo do processo de seleção dos monitores das disciplinas de graduação do IME no {!! $schoolterm->period !!} de {!! $schoolterm->year !!}}}\\[5cm]

   \hspace{.45\textwidth} %posiciona a minipage
  \vfill

\vspace{1cm}


\large \textbf{São Paulo}

\large \textbf{{!! now()->year !!}}

  \end{center}
\thispagestyle{empty}
\pagebreak
\end{titlepage}

\section*{ Visão Global}

No {!! $schoolterm->period !!} de {!! $schoolterm->year !!} foram cadastradas no Sistema Jupiter 
{!! $schoolterm->schoolclasses->count() !!} turmas, sendo 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->count() !!} do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->count() !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->count() !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->count() !!} do Departamento de Matemática.

No mesmo semestre foram solicitados {!! $schoolterm->schoolclasses->sum('requisition.requested_number') !!} monitores pelos docentes, sendo 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->get()->sum('requisition.requested_number') !!} do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->get()->sum('requisition.requested_number') !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->get()->sum('requisition.requested_number') !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->get()->sum('requisition.requested_number') !!} do Departamento de Matemática.

Foram feitas {!! $schoolterm->schoolclasses()->withCount('enrollments')->get()->sum('enrollments_count') !!} inscrições nas vagas de monitoria por parte dos alunos, sendo 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('enrollments')->get()->sum('enrollments_count') !!}  do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('enrollments')->get()->sum('enrollments_count') !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('enrollments')->get()->sum('enrollments_count') !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('enrollments')->get()->sum('enrollments_count') !!} do Departamento de Matemática.

Foram selecionados {!! $schoolterm->schoolclasses()->withCount('selections')->get()->sum('selections_count') !!} monitores no total, sendo
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('selections')->get()->sum('selections_count') !!}  do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('selections')->get()->sum('selections_count') !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('selections')->get()->sum('selections_count') !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('selections')->get()->sum('selections_count') !!} do Departamento de Matemática.

\begin{table}[h]
    \caption{Resumo dos dados gerais.}
    \begin{center}
        \begin{tabular}{ l c c c c c }
            \hline
            & Total & MAC & MAP & MAE & MAT\\
            \hline
            Turmas & {!! $schoolterm->schoolclasses->count() !!} & 
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->count() !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->count() !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->count() !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->count() !!} \\
            Vagas solicitadas &
            {!! $schoolterm->schoolclasses->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->get()->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->get()->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->get()->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->get()->sum('requisition.requested_number') !!} \\
            Inscrições &
            {!! $schoolterm->schoolclasses()->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('enrollments')->get()->sum('enrollments_count') !!} \\
            Monitores eleitos &
            {!! $schoolterm->schoolclasses()->withCount('selections')->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('selections')->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('selections')->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('selections')->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('selections')->get()->sum('selections_count') !!} \\
            \hline
        \end{tabular}
    \end{center}
\end{table}

\pagebreak
\section*{Detalhamento por Departamento}
\subsection*{Departamento de Ciência da Computação}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm}}
    \caption{Relação de monitores eleitos do Departamento de Ciência da Computação.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Ciência da Computação (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->has('selections')->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &     
    \href{mailto:{!! nomeAbrev($schoolclass->requisition->instructor->codema) !!}}{{!! nomeAbrev($schoolclass->requisition->instructor->nompes) !!}}  & 
    \makecell[l]{@foreach($schoolclass->selections as $selection) \href{mailto:{!! nomeAbrev($selection->student->codema) !!}}{{!! nomeAbrev($selection->student->nompes) !!}} {!! $selection->enrollment->voluntario ? "(Voluntário)" : "" !!}\\ @endforeach}
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}


\pagebreak
\subsection*{Departamento de Matemática Aplicada}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm}}
    \caption{Relação de monitores eleitos do Departamento de Matemática Aplicada.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Matemática Aplicada (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->has('selections')->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &   
    \href{mailto:{!! nomeAbrev($schoolclass->requisition->instructor->codema) !!}}{{!! nomeAbrev($schoolclass->requisition->instructor->nompes) !!}}  & 
    \makecell[l]{@foreach($schoolclass->selections as $selection) \href{mailto:{!! nomeAbrev($selection->student->codema) !!}}{{!! nomeAbrev($selection->student->nompes) !!}}\\ @endforeach}
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}


\pagebreak
\subsection*{Departamento de Estatística}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm}}
    \caption{Relação de monitores eleitos do Departamento de Estatística.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Estatística (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->has('selections')->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &   
    \href{mailto:{!! nomeAbrev($schoolclass->requisition->instructor->codema) !!}}{{!! nomeAbrev($schoolclass->requisition->instructor->nompes) !!}}  & 
    \makecell[l]{@foreach($schoolclass->selections as $selection) \href{mailto:{!! nomeAbrev($selection->student->codema) !!}}{{!! nomeAbrev($selection->student->nompes) !!}}\\ @endforeach}
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}


\pagebreak
\subsection*{Departamento de Matemática}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm}}
    \caption{Relação de monitores eleitos do Departamento de Matemática.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Matemática (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->has('selections')->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &   
    \href{mailto:{!! nomeAbrev($schoolclass->requisition->instructor->codema) !!}}{{!! nomeAbrev($schoolclass->requisition->instructor->nompes) !!}}  & 
    \makecell[l]{@foreach($schoolclass->selections as $selection) \href{mailto:{!! nomeAbrev($selection->student->codema) !!}}{{!! nomeAbrev($selection->student->nompes) !!}}\\ @endforeach}
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}

\end{document}

@php
function nomeAbrev($nometodo){

$pattern = '/ de | do | dos | da | das | e /i';
$nome = preg_replace($pattern,' ',$nometodo);
$nome = explode(' ', $nome);

$nomes_meio = ' ';

if(count($nome) > 2){
   for($x=1;$x<count($nome)-1;$x++){
      $nomes_meio .= $nome[$x][0].". ";
   }
}

$nomeabreviado = array_shift($nome).$nomes_meio.array_pop($nome);

return $nomeabreviado;

}
@endphp