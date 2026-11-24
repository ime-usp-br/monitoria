import os
import sys
from urllib.parse import quote_plus

os.environ.setdefault("MPLCONFIGDIR", "/tmp/matplotlib")
os.environ.setdefault("XDG_CACHE_HOME", "/tmp/.cache")
os.makedirs("/tmp/.cache", exist_ok=True)

try:
    import matplotlib.pyplot as plt
    import seaborn as sns
    import pandas as pd
    import pymysql
    import sqlalchemy as db
    from dotenv import load_dotenv

    load_dotenv()
    sns.set()

    if len(sys.argv) < 2 or not sys.argv[1].isdigit():
        raise ValueError("O identificador do período letivo deve ser numérico.")

    st_id = int(sys.argv[1])

    base_path = os.environ.get("BASE_PATH", "")
    path = os.path.join(base_path, "storage", "app", "graphs") + os.sep
    os.makedirs(path, exist_ok=True)

    engine = db.create_engine("mysql+pymysql://%s:%s@%s:%s/%s"%(
                                                                    quote_plus(os.environ.get("DB_USERNAME", "")),
                                                                    quote_plus(os.environ.get("DB_PASSWORD", "")),
                                                                    os.environ.get("DB_HOST"),
                                                                    os.environ.get("DB_PORT"),
                                                                    os.environ.get("DB_DATABASE")
                                                                ))

    st = pd.read_sql(db.text("select * from school_terms where id=:id"), engine, params={"id": st_id}).iloc[0].to_dict()
    sts = pd.read_sql("select * from school_terms", engine)
    selections = pd.read_sql("select * from selections", engine)
    scs = pd.read_sql("select * from school_classes", engine)
    departments = pd.read_sql("select * from departments", engine)

    selections = pd.merge(selections, scs.loc[:,["id","school_term_id","department_id"]], left_on="school_class_id", right_on="id")
    selections = pd.merge(selections, departments.loc[:,["id","nomabvset"]], left_on="department_id", right_on="id").rename(columns={"nomabvset":"Departamento"})
    selections["school_term_id"] = selections["school_term_id"].astype("category")
    selections["Departamento"] = selections["Departamento"].astype("category")

    monitorias_por_departamento = selections[selections["sitatl"]!="Desligado"].groupby(["school_term_id","Departamento"], observed=True).count().rename(columns={"id_x":"Monitorias"}).loc[:,["Monitorias"]]
    monitorias_por_departamento = monitorias_por_departamento.reset_index()
    monitorias_por_departamento = pd.merge(sts.loc[:,["id","year","period"]],monitorias_por_departamento,left_on="id", right_on="school_term_id").loc[:,["year","period","Monitorias", "Departamento"]]
    monitorias_por_departamento["Semestre"] = monitorias_por_departamento[["year","period"]].apply(lambda x: str(x["year"])+" "+x["period"].split(" ")[0]+" Sem", axis=1)
    monitorias_por_departamento = monitorias_por_departamento.loc[:,["Departamento","Monitorias","Semestre"]]
    monitorias_por_departamento = pd.concat([monitorias_por_departamento, pd.DataFrame([["MAC",0,"2022 1° Sem"],["MAE",0,"2022 1° Sem"],["MAT",0,"2022 1° Sem"],["MAP",0,"2022 1° Sem"]], columns=["Departamento","Monitorias","Semestre"])],ignore_index=True)
    monitorias_por_departamento.sort_values(by=["Semestre"], inplace=True)
    mac = monitorias_por_departamento[monitorias_por_departamento["Departamento"]=="MAC"].reset_index(drop=True)
    mat = monitorias_por_departamento[monitorias_por_departamento["Departamento"]=="MAT"].reset_index(drop=True)
    map_ = monitorias_por_departamento[monitorias_por_departamento["Departamento"]=="MAP"].reset_index(drop=True)
    mae = monitorias_por_departamento[monitorias_por_departamento["Departamento"]=="MAE"].reset_index(drop=True)


    fig, ax = plt.subplots(figsize=(10,7))
    plt.title("Monitorias por Departamento")
    plt.setp( ax.xaxis.get_majorticklabels(), rotation=-45, ha="left" )
    plt.ylabel("Monitorias")
    plt.xlabel("Semestres")
    ax.bar(mat["Semestre"],mat["Monitorias"],label="MAT")
    ax.bar(mac["Semestre"],mac["Monitorias"],label="MAC", bottom=mat["Monitorias"])
    ax.bar(mae["Semestre"],mae["Monitorias"],label="MAE", bottom=mat["Monitorias"]+mac["Monitorias"])
    ax.bar(map_["Semestre"],map_["Monitorias"],label="MAP", bottom=mat["Monitorias"]+mac["Monitorias"]+mae["Monitorias"])
    ax.legend()
    fig.subplots_adjust(bottom=0.2)
    plt.savefig(path+"monitorias_por_departamento.jpg")

    courses = pd.read_sql(db.text("select * from courses where schoolterm_id=:id"), engine, params={"id": st_id})
    courses = courses.loc[:, ~courses.columns.duplicated()]
    courses["Tipo"] = courses["nomcur"].astype(str).apply(lambda value: "Pós Graduação" if "Doutorado" in value or "Mestrado" in value else "Graduação")
    courses["Local"] = courses["sglund"].astype(str).apply(lambda value: "IME" if value == "IME" else "Fora do IME")
    courses_grouped = courses.groupby(["Tipo","Local"], observed=True).count().loc[:,["id"]].rename(columns={"id":"Monitorias"}).reset_index()
    courses_grouped = courses_grouped.loc[:, ~courses_grouped.columns.duplicated()]
    courses_grouped["label"] = courses_grouped["Tipo"].astype(str) + " " + courses_grouped["Local"].astype(str)
    outside = courses_grouped["Local"].astype(str).str.contains("Fora")
    courses_grouped.loc[~outside, "label"] = courses_grouped.loc[~outside, "Tipo"].astype(str) + " no " + courses_grouped.loc[~outside, "Local"].astype(str)

    fig, ax = plt.subplots(figsize=(10,7))
    plt.title("Perfil dos Monitores no "+st["period"]+" de "+str(st["year"]))
    plt.pie(courses_grouped["Monitorias"], labels=courses_grouped["label"], autopct='%1.1f%%', shadow=True)
    plt.savefig(path+"monitorias_pie_"+str(st["year"])+st["period"][0]+".jpg")


except Exception as ex:
    import traceback

    ex_type, ex_value, ex_traceback = sys.exc_info()

    trace_back = traceback.extract_tb(ex_traceback)

    stack_trace = list()

    for trace in trace_back:
        stack_trace.append("File : %s , Line : %d, Func.Name : %s, Message : %s" % (trace[0], trace[1], trace[2], trace[3]))

    print("Exception type : %s " % ex_type.__name__, file=sys.stderr)
    print("Exception message : %s" %ex_value, file=sys.stderr)
    print("Stack trace : %s" %stack_trace, file=sys.stderr)
    sys.exit(1)

