algoritmo "ATIVIDADE 17"
var
   i: inteiro
   nota, aprov, recu, repro: real
inicio
   aprov <- 0
   recu <- 0
   repro <- 0

   para i de 1 ate 20 faca
      escreva("Nota aluno ", i, ": ")
      leia(nota)

      se nota >= 7 entao
         aprov <- aprov + 1
      senao
         se nota >= 5 entao
            recu <- recu + 1
         senao
            repro <- repro + 1
         fimse
      fimse
   fimpara

   escreval("Aprovados: ", (aprov/20)*100, "%")
   escreval("Recuperação: ", (recu/20)*100, "%")
   escreval("Reprovados: ", (repro/20)*100, "%")
fimalgoritmo