algoritmo "ATIVIDADE 12"
var idade: inteiro
inicio
   escreva("Idade: ")
   leia(idade)
   se idade <= 12 entao
      escreva("Criança")
   senao
      se idade <= 17 entao
         escreva("Adolescente")
      senao
         se idade <= 59 entao
            escreva("Adulto")
         senao
            escreva("Idoso")
         fimse
      fimse
   fimse
fimalgoritmo