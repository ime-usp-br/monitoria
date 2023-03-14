try:
    import sys
    import matplotlib.pyplot as plt
    import seaborn as sns
    import pandas as pd
    import pymysql
    import sqlalchemy as db
    from dotenv import load_dotenv
    import os

    load_dotenv()
    sns.set()

    st_id = sys.argv[1]

    path = os.environ.get("BASE_PATH")+"/storage/app/graphs/"
    if not os.path.isdir(path):
        os.mkdir(path)

    engine = db.create_engine("mysql+pymysql://%s:%s@%s:%s/%s"%(
                                                                    os.environ.get("DB_USERNAME"),
                                                                    os.environ.get("DB_PASSWORD"),
                                                                    os.environ.get("DB_HOST"),
                                                                    os.environ.get("DB_PORT"),
                                                                    os.environ.get("DB_DATABASE")
                                                                ))

    st = pd.read_sql("select * from school_terms where id="+st_id, engine).iloc[0].to_dict()
    sts = pd.read_sql("select * from school_terms", engine)
    selections = pd.read_sql("select * from selections", engine)
    scs = pd.read_sql("select * from school_classes", engine)
    departments = pd.read_sql("select * from departments", engine)

    selections = pd.merge(selections, scs.loc[:,["id","school_term_id","department_id"]], left_on="school_class_id", right_on="id")
    selections = pd.merge(selections, departments.loc[:,["id","nomabvset"]], left_on="department_id", right_on="id").rename(columns={"nomabvset":"Departamento"})
    selections["school_term_id"] = selections["school_term_id"].astype("category")
    selections["Departamento"] = selections["Departamento"].astype("category")

    monitorias_por_departamento = selections[selections["sitatl"]!="Desligado"].groupby(["school_term_id","Departamento"]).count().rename(columns={"id_x":"Monitorias"}).loc[:,["Monitorias"]]
    monitorias_por_departamento = monitorias_por_departamento.reset_index()
    monitorias_por_departamento = pd.merge(sts.loc[:,["id","year","period"]],monitorias_por_departamento,left_on="id", right_on="school_term_id").loc[:,["year","period","Monitorias", "Departamento"]]
    monitorias_por_departamento["Semestre"] = monitorias_por_departamento[["year","period"]].apply(lambda x: str(x[0])+" "+x[1].split(" ")[0]+" Sem", axis=1)
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

    courses = pd.read_sql("select * from courses where schoolterm_id="+st_id, engine)
    courses["Tipo"] = courses.apply(lambda row: "Pós Graduação" if "Doutorado" in row["nomcur"] or "Mestrado" in row["nomcur"] else "Graduação", axis=1)
    courses["Local"] = courses.apply(lambda row: "IME" if "IME" == row["sglund"] else "Fora do IME", axis=1)
    courses_grouped = courses.groupby(["Tipo","Local"]).count().loc[:,["id"]].rename(columns={"id":"Monitorias"}).reset_index()
    courses_grouped["label"] = courses_grouped.apply(lambda row: row["Tipo"]+" "+row["Local"] if "Fora" in row["Local"] else row["Tipo"]+" no "+row["Local"], axis=1)

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

    print("Exception type : %s " % ex_type.__name__)
    print("Exception message : %s" %ex_value)
    print("Stack trace : %s" %stack_trace)

