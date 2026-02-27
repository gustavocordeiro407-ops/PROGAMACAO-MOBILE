algoritmo "ATIVIDADE 04"
var
   i, n, qtd1, qtd2, qtd3: inteiro
inicio
   qtd1 <- 0 // 0 a 25
   qtd2 <- 0 // 26 a 50
   qtd3 <- 0 // Acima de 50

   para i de 1 ate 30 faca
      escreva("Digite número ", i, ": ")
      leia(n)
      se (n >= 0) e (n <= 25) entao
         qtd1 <- qtd1 + 1
      senao
         se (n >= 26) e (n <= 50) entao
            qtd2 <- qtd2 + 1
         senao
            se n > 50 entao
               qtd3 <- qtd3 + 1
            fimse
         fimse
      fimse
   fimpara
   escreval("Entre 0 e 25: ", qtd1)
   escreval("Entre 26 e 50: ", qtd2)
   escreval("Acima de 50: ", qtd3)
fimalgoritmo